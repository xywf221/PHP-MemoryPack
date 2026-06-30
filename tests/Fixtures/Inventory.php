<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Attributes\Int32Field;
use MemoryPack\Mapping\Attributes\ObjectField;
use MemoryPack\Mapping\Attributes\StringField;
use MemoryPack\Mapping\Type;

#[MemoryPackable]
final class Inventory
{
    #[MemoryPackField(order: 0, type: Type::DICT, key: new StringField(), element: new Int32Field())]
    public array $counts;

    #[MemoryPackField(order: 1, type: Type::DICT, key: new StringField(), element: new ObjectField(Point::class))]
    public array $locations;
}
