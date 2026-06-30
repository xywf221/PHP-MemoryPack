<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use Attribute;
use MemoryPack\Mapping\Type;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MemoryPackField
{
    public function __construct(
        public private(set) int|null $order = null,
        public private(set) string|null $type = null,
        public private(set) bool|null $nullable = null,
        public private(set) string|null $class = null,
        public private(set) string|null $format = null,
        public private(set) string|null $formatter = null,
        public private(set) self|null $element = null,
        public private(set) self|null $key = null,
    ) {
    }

    public static function of(string $type, bool|null $nullable = null): self
    {
        return new self(type: $type, nullable: $nullable);
    }

    public static function boolOf(bool|null $nullable = null): self
    {
        return self::of(Type::BOOL, $nullable);
    }

    public static function int8Of(bool|null $nullable = null): self
    {
        return self::of(Type::INT8, $nullable);
    }

    public static function uint8Of(bool|null $nullable = null): self
    {
        return self::of(Type::UINT8, $nullable);
    }

    public static function int16Of(bool|null $nullable = null): self
    {
        return self::of(Type::INT16, $nullable);
    }

    public static function uint16Of(bool|null $nullable = null): self
    {
        return self::of(Type::UINT16, $nullable);
    }

    public static function int32Of(bool|null $nullable = null): self
    {
        return self::of(Type::INT32, $nullable);
    }

    public static function uint32Of(bool|null $nullable = null): self
    {
        return self::of(Type::UINT32, $nullable);
    }

    public static function int64Of(bool|null $nullable = null): self
    {
        return self::of(Type::INT64, $nullable);
    }

    public static function float32Of(bool|null $nullable = null): self
    {
        return self::of(Type::FLOAT32, $nullable);
    }

    public static function float64Of(bool|null $nullable = null): self
    {
        return self::of(Type::FLOAT64, $nullable);
    }

    public static function stringOf(bool|null $nullable = null): self
    {
        return self::of(Type::STRING, $nullable);
    }

    public static function dateTimeOf(bool|null $nullable = null, string|null $format = null): self
    {
        return new self(type: Type::DATETIME, nullable: $nullable, format: $format);
    }

    public static function jsonOf(bool|null $nullable = null): self
    {
        return self::of(Type::JSON, $nullable);
    }

    public static function objectOf(string $class, bool|null $nullable = null): self
    {
        return new self(nullable: $nullable, class: $class);
    }

    public static function listOf(self $element, bool|null $nullable = null): self
    {
        return new self(type: Type::LIST, nullable: $nullable, element: $element);
    }

    public static function dictOf(self $key, self $element, bool|null $nullable = null): self
    {
        return new self(type: Type::DICT, nullable: $nullable, element: $element, key: $key);
    }
}
