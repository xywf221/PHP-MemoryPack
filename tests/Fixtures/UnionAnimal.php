<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Attributes\MemoryPackUnion;

#[MemoryPackable]
#[MemoryPackUnion(0, UnionCat::class)]
#[MemoryPackUnion(250, UnionDog::class)]
interface UnionAnimal
{
}
