<?php

declare(strict_types=1);

namespace MemoryPack\Mapping;

use MemoryPack\Formatters\MemoryPackFormatterInterface;

final class FieldDefinition
{
    public function __construct(
        public private(set) string $name,
        public private(set) string $type,
        public private(set) bool $nullable = false,
        public private(set) FieldDefinition|null $element = null,
        public private(set) FieldDefinition|null $key = null,
        public private(set) string|MemoryPackFormatterInterface|null $formatterClass = null,
        public private(set) string|null $className = null,
        public private(set) bool $valueType = false,
        public private(set) string|null $propertyName = null,
    ) {
    }

    public static function of(string $name, string $type, bool $nullable = false): self
    {
        return new self($name, $type, $nullable);
    }

    public static function listOf(string $name, self $element, bool $nullable = false): self
    {
        return new self($name, Type::LIST, $nullable, $element);
    }

    public static function dictOf(string $name, self $key, self $value, bool $nullable = false): self
    {
        return new self($name, Type::DICT, $nullable, $value, $key);
    }

    public function withFormatter(string|MemoryPackFormatterInterface $formatterClass): self
    {
        return new self($this->name, $this->type, $this->nullable, $this->element, $this->key, $formatterClass, $this->className, $this->valueType, $this->propertyName);
    }
}
