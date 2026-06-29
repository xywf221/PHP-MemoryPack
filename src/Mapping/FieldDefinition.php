<?php

declare(strict_types=1);

namespace MemoryPack\Mapping;

class FieldDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $nullable = false,
        public readonly FieldDefinition|null $element = null,
        public readonly FieldDefinition|null $key = null,
        public readonly string|null $formatterClass = null,
        public readonly string|null $format = null,
        public readonly string|null $className = null,
        public readonly bool $valueType = false,
        public readonly string|null $propertyName = null,
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

    public function withFormatter(string $formatterClass): self
    {
        return new self($this->name, $this->type, $this->nullable, $this->element, $this->key, $formatterClass, $this->format, $this->className, $this->valueType, $this->propertyName);
    }

    public function withFormat(string $format): self
    {
        return new self($this->name, $this->type, $this->nullable, $this->element, $this->key, $this->formatterClass, $format, $this->className, $this->valueType, $this->propertyName);
    }
}
