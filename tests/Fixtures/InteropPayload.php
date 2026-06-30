<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Attributes\Int32Field;
use MemoryPack\Mapping\Attributes\StringField;
use MemoryPack\Mapping\Type;

#[MemoryPackable]
final class InteropPayload
{
    #[MemoryPackField(order: 0, type: Type::INT32)]
    public int $id;

    #[MemoryPackField(order: 1)]
    public string $name;

    #[MemoryPackField(order: 2)]
    public bool $active;

    #[MemoryPackField(order: 3, type: Type::LIST, element: new Int32Field())]
    public array $scores;

    #[MemoryPackField(order: 4, type: Type::LIST, element: new StringField())]
    public array $tags;

    #[MemoryPackField(order: 5, type: Type::DICT, key: new StringField(), element: new Int32Field())]
    public array $counts;

    #[MemoryPackField(order: 6)]
    public Point $origin;
}
