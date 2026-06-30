<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class MemoryPackUnionTag
{
    public function __construct(public private(set) int $tag)
    {
    }
}
