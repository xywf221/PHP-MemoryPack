<?php

declare(strict_types=1);

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\Exception\MemoryPackException;
use MemoryPack\MemoryPackSerializer;
use MemoryPack\Mapping\FieldDefinition;
use MemoryPack\Mapping\Type;
use MemoryPack\Tests\Fixtures\A;
use MemoryPack\Tests\Fixtures\B;
use MemoryPack\Tests\Fixtures\C;
use MemoryPack\Tests\Fixtures\Inventory;
use MemoryPack\Tests\Fixtures\InteropPayload;
use MemoryPack\Tests\Fixtures\Player;
use MemoryPack\Tests\Fixtures\Point;
use MemoryPack\Tests\Fixtures\Shape;

it('serializes schema driven values using the MemoryPack wire format', function (): void {
    $schema = [
        FieldDefinition::of('id', Type::INT32),
        FieldDefinition::of('name', Type::STRING),
        FieldDefinition::of('active', Type::BOOL),
        FieldDefinition::listOf('scores', FieldDefinition::of('score', Type::INT32), nullable: true),
        FieldDefinition::of('ratio', Type::FLOAT64),
    ];

    $payload = MemoryPackSerializer::serialize($schema, [
        'id' => 42,
        'name' => '雷少',
        'active' => true,
        'scores' => [3, 5, 8],
        'ratio' => 1.5,
    ]);

    expect(bin2hex($payload[0]))->toBe('05')
        ->and(bin2hex(substr($payload, 1, 4)))->toBe('2a000000')
        ->and(bin2hex(substr($payload, 5, 14)))->toBe('f9ffffff02000000e99bb7e5b091');

    $result = MemoryPackSerializer::deserialize($schema, $payload);

    expect($result)->toMatchArray([
        'id' => 42,
        'name' => '雷少',
        'active' => true,
        'scores' => [3, 5, 8],
    ])->and($result['ratio'])->toBe(1.5);
});

it('round trips null objects and nullable strings', function (): void {
    $schema = [FieldDefinition::of('name', Type::STRING, nullable: true)];

    expect(bin2hex(MemoryPackSerializer::serialize($schema, null)))->toBe('ff')
        ->and(MemoryPackSerializer::deserialize($schema, "\xff"))->toBeNull();

    $writer = new MemoryPackWriter();
    $writer->writeObjectHeader(1);
    $writer->writeNullString();

    expect(MemoryPackSerializer::deserialize($schema, $writer->bytes()))->toBe(['name' => null]);
});

it('rejects truncated payloads', function (): void {
    $writer = new MemoryPackWriter();
    $writer->writeObjectHeader(1);
    $writer->writeInt32(2);
    $writer->writeRaw("\x11");

    MemoryPackSerializer::deserialize([FieldDefinition::of('id', Type::INT32)], $writer->bytes());
})->throws(MemoryPackException::class);

it('reads utf16 strings and negative int64 values', function (): void {
    $reader = new MemoryPackReader("\x02\x00\x00\x00R\x00e\x00");
    expect($reader->readString())->toBe('Re');

    $writer = new MemoryPackWriter();
    $writer->writeInt64(-1);
    $writer->writeInt64(PHP_INT_MIN);
    $reader = new MemoryPackReader($writer->bytes());

    expect($reader->readInt64())->toBe(-1)
        ->and($reader->readInt64())->toBe(PHP_INT_MIN);
});

it('round trips nested objects recursively', function (): void {
    $a = new A();
    $a->b = new B();
    $a->b->c = new C();
    $a->b->c->name = 'leaf';

    $payload = MemoryPackSerializer::serializeObject($a);
    $result = MemoryPackSerializer::deserializeObject(A::class, $payload);

    expect($result)->toBeInstanceOf(A::class)
        ->and($result->b)->toBeInstanceOf(B::class)
        ->and($result->b->c)->toBeInstanceOf(C::class)
        ->and($result->b->c->name)->toBe('leaf');
});

it('serializes value object mappings without nested object headers like C# structs', function (): void {
    $shape = new Shape();
    $shape->origin = new Point();
    $shape->origin->x = 1;
    $shape->origin->y = 2;
    $shape->points = [];

    $payload = MemoryPackSerializer::serializeObject($shape);

    expect(bin2hex(substr($payload, 0, 10)))->toBe('02010000000200000000');

    $result = MemoryPackSerializer::deserializeObject(Shape::class, $payload);

    expect($result)->toBeInstanceOf(Shape::class)
        ->and($result->origin)->toBeInstanceOf(Point::class)
        ->and($result->origin->x)->toBe(1)
        ->and($result->origin->y)->toBe(2)
        ->and($result->points)->toBe([]);
});

it('builds schemas from attributes and uses custom formatters', function (): void {
    $player = new Player();
    $player->id = 7;
    $player->name = 'abc';
    $player->scores = [10, 20];
    $player->createdAt = new DateTimeImmutable('2026-06-29 12:00:00+00:00');

    $payload = MemoryPackSerializer::serializeObject($player);
    $result = MemoryPackSerializer::deserializeObject(Player::class, $payload);

    expect($result)->toBeInstanceOf(Player::class)
        ->and($result->id)->toBe(7)
        ->and($result->name)->toBe('abc')
        ->and($result->scores)->toBe([10, 20])
        ->and($result->createdAt?->format('Y-m-d'))->toBe('2026-06-29');
});

it('round trips dictionary fields as collection header followed by key value pairs', function (): void {
    $schema = [
        FieldDefinition::dictOf(
            'scores',
            FieldDefinition::of('key', Type::STRING),
            FieldDefinition::of('value', Type::INT32),
        ),
    ];

    $payload = MemoryPackSerializer::serialize($schema, [
        'scores' => ['alpha' => 10, 'beta' => 20],
    ]);

    expect(bin2hex(substr($payload, 0, 5)))->toBe('0102000000');

    $result = MemoryPackSerializer::deserialize($schema, $payload);

    expect($result)->toBe(['scores' => ['alpha' => 10, 'beta' => 20]]);
});

it('round trips dictionary attributes with value object values', function (): void {
    $inventory = new Inventory();
    $inventory->counts = ['sword' => 2, 'potion' => 5];

    $point = new Point();
    $point->x = 9;
    $point->y = 4;
    $inventory->locations = ['spawn' => $point];

    $payload = MemoryPackSerializer::serializeObject($inventory);
    $result = MemoryPackSerializer::deserializeObject(Inventory::class, $payload);

    expect($result)->toBeInstanceOf(Inventory::class)
        ->and($result->counts)->toBe(['sword' => 2, 'potion' => 5])
        ->and($result->locations['spawn'])->toBeInstanceOf(Point::class)
        ->and($result->locations['spawn']->x)->toBe(9)
        ->and($result->locations['spawn']->y)->toBe(4);
});
it('interoperates with real C# MemoryPack serialization', function (): void {
    $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'CSharpInterop.cs';

    $csharpPayload = trim(runCommand(['dotnet', 'run', $script, '--', 'write']));
    $fromCsharp = MemoryPackSerializer::deserializeObject(InteropPayload::class, base64_decode($csharpPayload, true));

    expect($fromCsharp)->toBeInstanceOf(InteropPayload::class)
        ->and($fromCsharp->id)->toBe(42)
        ->and($fromCsharp->name)->toBe('雷少')
        ->and($fromCsharp->active)->toBeTrue()
        ->and($fromCsharp->scores)->toBe([3, 5, 8])
        ->and($fromCsharp->tags)->toBe(['php', 'csharp'])
        ->and($fromCsharp->counts)->toBe(['alpha' => 10, 'beta' => 20])
        ->and($fromCsharp->origin)->toBeInstanceOf(Point::class)
        ->and($fromCsharp->origin->x)->toBe(9)
        ->and($fromCsharp->origin->y)->toBe(4);

    $payload = new InteropPayload();
    $payload->id = 42;
    $payload->name = '雷少';
    $payload->active = true;
    $payload->scores = [3, 5, 8];
    $payload->tags = ['php', 'csharp'];
    $payload->counts = ['alpha' => 10, 'beta' => 20];
    $payload->origin = new Point();
    $payload->origin->x = 9;
    $payload->origin->y = 4;

    $phpPayload = base64_encode(MemoryPackSerializer::serializeObject($payload));
    expect(trim(runCommand(['dotnet', 'run', $script, '--', 'read', $phpPayload])))->toBe('ok');
});

function runCommand(array $command): string
{
    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__));
    if (!is_resource($process)) {
        throw new RuntimeException('Failed to start process.');
    }

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        throw new RuntimeException("Command failed with exit code {$exitCode}: {$stderr}");
    }

    return $stdout;
}
