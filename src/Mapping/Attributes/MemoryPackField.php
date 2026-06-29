<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class MemoryPackField
{
    public function __construct(
        public readonly int|null $order = null,
        public readonly string|null $type = null,
        public readonly bool|null $nullable = null,
        public readonly string|null $elementType = null,
        public readonly string|null $elementClass = null,
        public readonly string|null $keyType = null,
        public readonly string|null $keyClass = null,
        public readonly string|null $class = null,
        public readonly string|null $format = null,
        public readonly string|null $formatter = null,
    ) {
    }
}
