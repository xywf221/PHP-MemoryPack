<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Type;

#[MemoryPackable(valueType: true)]
final class Point
{
    #[MemoryPackField(order: 0, type: Type::INT32)]
    public int $x;

    #[MemoryPackField(order: 1, type: Type::INT32)]
    public int $y;
}
