<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\ListField;
use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Attributes\ObjectField;
use MemoryPack\Mapping\Type;

#[MemoryPackable]
final class UnionZoo
{
    #[MemoryPackField(order: 0, nullable: true)]
    public UnionAnimal|null $favorite;

    #[MemoryPackField(order: 1, type: Type::LIST, element: new ObjectField(UnionAnimal::class))]
    public array $animals;
}
