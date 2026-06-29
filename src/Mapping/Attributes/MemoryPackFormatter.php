<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class MemoryPackFormatter
{
    public function __construct(public readonly string $formatterClass)
    {
    }
}
