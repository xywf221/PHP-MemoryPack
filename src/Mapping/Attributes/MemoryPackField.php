<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class MemoryPackField
{
    public function __construct(
        public private(set) int|null $order = null,
        public private(set) string|null $type = null,
        public private(set) bool|null $nullable = null,
        public private(set) string|null $elementType = null,
        public private(set) string|null $elementClass = null,
        public private(set) string|null $keyType = null,
        public private(set) string|null $keyClass = null,
        public private(set) string|null $class = null,
        public private(set) string|null $format = null,
        public private(set) string|null $formatter = null,
    ) {
    }
}
