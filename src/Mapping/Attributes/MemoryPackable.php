<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class MemoryPackable
{
    public function __construct(public readonly bool $valueType = false)
    {
    }
}
