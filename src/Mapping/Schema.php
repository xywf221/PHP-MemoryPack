<?php

declare(strict_types=1);

namespace MemoryPack\Mapping;

final class Schema
{
    /**
     * @param list<FieldDefinition> $fields
     */
    public function __construct(
        public readonly array $fields,
        public readonly string|null $className = null,
        public readonly bool $valueType = false,
    ) {
    }
}
