<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Type;

#[MemoryPackable]
final class Inventory
{
    #[MemoryPackField(order: 0, type: Type::DICT, keyType: Type::STRING, elementType: Type::INT32)]
    public array $counts;

    #[MemoryPackField(order: 1, type: Type::DICT, keyType: Type::STRING, elementClass: Point::class)]
    public array $locations;
}
