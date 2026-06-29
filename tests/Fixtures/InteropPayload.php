<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
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

    #[MemoryPackField(order: 3, type: Type::LIST, elementType: Type::INT32)]
    public array $scores;

    #[MemoryPackField(order: 4, type: Type::LIST, elementType: Type::STRING)]
    public array $tags;

    #[MemoryPackField(order: 5, type: Type::DICT, keyType: Type::STRING, elementType: Type::INT32)]
    public array $counts;

    #[MemoryPackField(order: 6)]
    public Point $origin;
}
