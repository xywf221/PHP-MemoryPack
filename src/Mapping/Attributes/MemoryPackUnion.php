<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class MemoryPackUnion
{
    /**
     * @param class-string $class
     */
    public function __construct(
        public private(set) int $tag,
        public private(set) string $class,
    ) {
    }
}
