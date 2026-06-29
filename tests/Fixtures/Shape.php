<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Type;

#[MemoryPackable]
final class Shape
{
    #[MemoryPackField(order: 0)]
    public Point $origin;

    #[MemoryPackField(order: 1, type: Type::LIST, elementClass: Point::class)]
    public array $points;
}
