<?php

declare(strict_types=1);

namespace MemoryPack\Mapping;

final class Schema
{
    /**
     * @param list<FieldDefinition> $fields
     */
    public function __construct(
        public private(set) array $fields,
        public private(set) string|null $className = null,
        public private(set) bool $valueType = false,
    ) {
    }
}
