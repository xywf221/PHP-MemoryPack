<?php

declare(strict_types=1);

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\Exception\MemoryPackException;
use MemoryPack\MemoryPackSerializer;
use MemoryPack\Mapping\Attributes\MemoryPackField as AttributeField;
use MemoryPack\Mapping\FieldDefinition;
use MemoryPack\Mapping\Type;
use MemoryPack\Tests\Fixtures\A;
use MemoryPack\Tests\Fixtures\B;
use MemoryPack\Tests\Fixtures\C;
use MemoryPack\Tests\Fixtures\EquipmentItem;
use MemoryPack\Tests\Fixtures\Inventory;
use MemoryPack\Tests\Fixtures\RandomOption;
use MemoryPack\Tests\Fixtures\InteropPayload;
use MemoryPack\Tests\Fixtures\Forecast;
use MemoryPack\Tests\Fixtures\PackageData;
use MemoryPack\Tests\Fixtures\PackageItem;
use MemoryPack\Tests\Fixtures\Player;
use MemoryPack\Tests\Fixtures\Point;
use MemoryPack\Tests\Fixtures\Shape;
use MemoryPack\Tests\Fixtures\Temperature;
use MemoryPack\Tests\Fixtures\Utf16Payload;
use MemoryPack\Formatters\Utf16StringFormatter;

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
        ->and(bin2hex(substr($payload, 5, 14)))->toBe('f9ffffff00000000e99bb7e5b091');

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

it('writes nullable primitive nulls as zero values', function (): void {
    $schema = [
        FieldDefinition::of('active', Type::BOOL, nullable: true),
        FieldDefinition::of('count', Type::INT32, nullable: true),
        FieldDefinition::of('ratio', Type::FLOAT64, nullable: true),
    ];

    $payload = MemoryPackSerializer::serialize($schema, [
        'active' => null,
        'count' => null,
        'ratio' => null,
    ]);

    expect(bin2hex($payload))->toBe('0300000000000000000000000000');
    expect(MemoryPackSerializer::deserialize($schema, $payload))->toBe([
        'active' => false,
        'count' => 0,
        'ratio' => 0.0,
    ]);
});

it('builds field metadata with MemoryPackField helpers', function (): void {
    $field = AttributeField::dictOf(
        AttributeField::stringOf(),
        AttributeField::listOf(AttributeField::int32Of()),
    );

    expect($field->type)->toBe(Type::DICT)
        ->and($field->key?->type)->toBe(Type::STRING)
        ->and($field->element?->type)->toBe(Type::LIST)
        ->and($field->element?->element?->type)->toBe(Type::INT32);
});

it('rejects truncated payloads', function (): void {
    $writer = new MemoryPackWriter();
    $writer->writeObjectHeader(1);
    $writer->writeInt32(2);
    $writer->writeRaw("\x11");

    MemoryPackSerializer::deserialize([FieldDefinition::of('id', Type::INT32)], $writer->bytes());
})->throws(MemoryPackException::class);

it('reads default utf8 strings and negative int64 values', function (): void {
    $reader = new MemoryPackReader("\xf9\xff\xff\xff\x00\x00\x00\x00\xe9\x9b\xb7\xe5\xb0\x91");
    expect($reader->readString())->toBe('雷少');

    $writer = new MemoryPackWriter();
    $writer->writeInt64(-1);
    $writer->writeInt64(PHP_INT_MIN);
    $reader = new MemoryPackReader($writer->bytes());

    expect($reader->readInt64())->toBe(-1)
        ->and($reader->readInt64())->toBe(PHP_INT_MIN);
});

it('keeps string fields on the default utf8 wire format', function (): void {
    $schema = [FieldDefinition::of('name', Type::STRING)];
    $payload = MemoryPackSerializer::serialize($schema, ['name' => '雷少']);

    expect(bin2hex(substr($payload, 1)))->toBe('f9ffffff00000000e99bb7e5b091');
    expect(MemoryPackSerializer::deserialize($schema, $payload))->toBe(['name' => '雷少']);
});

it('round trips utf16 formatter payloads with csharp', function (): void {
    $schema = [FieldDefinition::of('name', Type::STRING)->withFormatter(Utf16StringFormatter::class)];
    $payload = MemoryPackSerializer::serialize($schema, ['name' => '雷少']);

    expect(bin2hex($payload))->toBe('0102000000f796115c');
    expect(MemoryPackSerializer::deserialize($schema, $payload))->toBe(['name' => '雷少']);

    $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'CSharpInterop.cs';
    $csharpPayload = trim(runCommand(['dotnet', 'run', $script, '--', 'utf16-write']));
    expect(bin2hex(base64_decode($csharpPayload, true)))->toBe('0102000000f796115c');

    $phpPayload = base64_encode($payload);
    expect(trim(runCommand(['dotnet', 'run', $script, '--', 'utf16-read', $phpPayload])))->toBe('ok');
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

it('round trips nested list schemas', function (): void {
    $schema = [
        FieldDefinition::listOf(
            'selections',
            FieldDefinition::listOf('row', FieldDefinition::of('item', Type::INT32)),
        ),
    ];

    $payload = MemoryPackSerializer::serialize($schema, [
        'selections' => [[1, 2], [3]],
    ]);

    expect(bin2hex($payload))->toBe('01020000000200000001000000020000000100000003000000');
    expect(MemoryPackSerializer::deserialize($schema, $payload))->toBe([
        'selections' => [[1, 2], [3]],
    ]);
});

it('round trips nested list attributes with object elements', function (): void {
    $first = new PackageItem();
    $first->id = 1;
    $second = new PackageItem();
    $second->id = 2;
    $third = new PackageItem();
    $third->id = 3;

    $data = new PackageData();
    $data->selections = [[$first, $second], [$third]];

    $payload = MemoryPackSerializer::serializeObject($data);
    $result = MemoryPackSerializer::deserializeObject(PackageData::class, $payload);

    expect(bin2hex($payload))->toBe('01020000000200000001010000000102000000010000000103000000');
    expect($result)->toBeInstanceOf(PackageData::class)
        ->and($result->selections[0][0])->toBeInstanceOf(PackageItem::class)
        ->and($result->selections[0][0]->id)->toBe(1)
        ->and($result->selections[0][1]->id)->toBe(2)
        ->and($result->selections[1][0]->id)->toBe(3);

    $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'CSharpInterop.cs';
    $csharpPayload = trim(runCommand(['dotnet', 'run', $script, '--', 'package-write']));
    expect(bin2hex(base64_decode($csharpPayload, true)))->toBe(bin2hex($payload));

    $phpPayload = base64_encode($payload);
    expect(trim(runCommand(['dotnet', 'run', $script, '--', 'package-read', $phpPayload])))->toBe('ok');
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

it('delegates to self-serializing types at every object boundary', function (): void {
    $forecast = new Forecast();
    $forecast->current = Temperature::of(21);
    $forecast->hourly = [Temperature::of(18), Temperature::of(23)];
    $forecast->byCity = ['oslo' => Temperature::of(-4)];

    $payload = MemoryPackSerializer::serializeObject($forecast);

    // object header (3 fields), bare int32 for current, then list and dict of bare int32 temperatures.
    expect(bin2hex($payload))->toBe('03' . '15000000' . '02000000' . '12000000' . '17000000' . '01000000' . 'fbffffff00000000' . '6f736c6f' . 'fcffffff');

    $result = MemoryPackSerializer::deserializeObject(Forecast::class, $payload);

    expect($result)->toBeInstanceOf(Forecast::class)
        ->and($result->current)->toBeInstanceOf(Temperature::class)
        ->and($result->current->celsius)->toBe(21)
        ->and($result->hourly[0]->celsius)->toBe(18)
        ->and($result->hourly[1]->celsius)->toBe(23)
        ->and($result->byCity['oslo'])->toBeInstanceOf(Temperature::class)
        ->and($result->byCity['oslo']->celsius)->toBe(-4);
});

it('round trips a self-serializing type at the top level', function (): void {
    $payload = MemoryPackSerializer::serializeObject(Temperature::of(7));

    expect(bin2hex($payload))->toBe('07000000');

    $result = MemoryPackSerializer::deserializeObject(Temperature::class, $payload);

    expect($result)->toBeInstanceOf(Temperature::class)
        ->and($result->celsius)->toBe(7);
});

it('writes unmanaged scalars then a nested packable via writePackable', function (): void {
    $item = new EquipmentItem();
    $item->itemId = 1001;
    $item->sealed = true;
    $item->durability = 250;
    $item->randomOption = RandomOption::of(7, -3);

    $payload = MemoryPackSerializer::serializeObject($item);

    // uint32 itemId, bool sealed, uint16 durability, then the nested packable's own bytes.
    expect(bin2hex($payload))->toBe('e9030000' . '01' . 'fa00' . '07000000' . 'fdffffff');

    $result = MemoryPackSerializer::deserializeObject(EquipmentItem::class, $payload);

    expect($result)->toBeInstanceOf(EquipmentItem::class)
        ->and($result->itemId)->toBe(1001)
        ->and($result->sealed)->toBeTrue()
        ->and($result->durability)->toBe(250)
        ->and($result->randomOption)->toBeInstanceOf(RandomOption::class)
        ->and($result->randomOption->optionId)->toBe(7)
        ->and($result->randomOption->value)->toBe(-3);
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
