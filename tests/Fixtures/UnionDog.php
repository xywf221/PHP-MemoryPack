<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Attributes\MemoryPackUnionTag;
use MemoryPack\Mapping\Type;

#[MemoryPackable]
#[MemoryPackUnionTag(250)]
final class UnionDog implements UnionAnimal
{
    #[MemoryPackField(order: 0, type: Type::STRING)]
    public string $name;
}
