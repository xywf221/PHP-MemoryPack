<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Attributes\MemoryPackUnion;

#[MemoryPackable]
#[MemoryPackUnion(1, UnionCircle::class)]
abstract class UnionShape
{
}
