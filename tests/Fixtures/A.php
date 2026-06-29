<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Type;

#[MemoryPackable]
final class A
{
    #[MemoryPackField(order: 0, type: Type::OBJECT)]
    public B $b;
}
