<?php

declare(strict_types=1);

namespace MemoryPack\Formatters;

use MemoryPack\Mapping\FieldDefinition;
use MemoryPack\Mapping\Type;

final class FormatterRegistry
{
    /**
     * @var array<string, MemoryPackFormatterInterface>
     */
    private array $formatters = [];

    public function __construct()
    {
        $this->register(Type::BOOL, new PrimitiveFormatter(Type::BOOL));
        $this->register(Type::UINT8, new PrimitiveFormatter(Type::UINT8));
        $this->register(Type::INT16, new PrimitiveFormatter(Type::INT16));
        $this->register(Type::UINT16, new PrimitiveFormatter(Type::UINT16));
        $this->register(Type::INT32, new PrimitiveFormatter(Type::INT32));
        $this->register(Type::UINT32, new PrimitiveFormatter(Type::UINT32));
        $this->register(Type::INT64, new PrimitiveFormatter(Type::INT64));
        $this->register(Type::FLOAT32, new PrimitiveFormatter(Type::FLOAT32));
        $this->register(Type::FLOAT64, new PrimitiveFormatter(Type::FLOAT64));
        $this->register(Type::STRING, new StringFormatter());
        $this->register(Type::LIST, new ListFormatter());
        $this->register(Type::DICT, new DictionaryFormatter());
        $this->register(Type::DATETIME, new DateTimeFormatter());
        $this->register(Type::JSON, new JsonFormatter());
    }

    public function register(string $type, MemoryPackFormatterInterface $formatter): void
    {
        $this->formatters[$type] = $formatter;
    }

    public function get(string $type): MemoryPackFormatterInterface
    {
        return $this->formatters[$type] ?? throw new \InvalidArgumentException("No formatter registered for type {$type}.");
    }

    public function resolve(FieldDefinition $field): MemoryPackFormatterInterface
    {
        if ($field->formatterClass !== null) {
            $formatter = new $field->formatterClass();
            if (!$formatter instanceof MemoryPackFormatterInterface) {
                throw new \InvalidArgumentException("Formatter {$field->formatterClass} must implement MemoryPackFormatterInterface.");
            }

            return $formatter;
        }

        return $this->get($field->type);
    }
}


