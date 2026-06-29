<?php

declare(strict_types=1);

namespace MemoryPack\Formatters;

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\MemoryPackSerializer;
use MemoryPack\Mapping\FieldDefinition;
use MemoryPack\Mapping\Type;

final class DictionaryFormatter implements MemoryPackFormatterInterface
{
    #[\Override]
    public function serialize(MemoryPackWriter $writer, mixed $value, FieldDefinition $field, FormatterRegistry $registry): void
    {
        if ($field->key === null || $field->element === null) {
            throw new \InvalidArgumentException("Dictionary field {$field->name} is missing key or value metadata.");
        }
        if ($value === null) {
            if (!$field->nullable) {
                throw new \InvalidArgumentException("Field {$field->name} cannot be null.");
            }
            $writer->writeNullCollection();
            return;
        }
        if (!is_array($value)) {
            throw new \InvalidArgumentException("Field {$field->name} must be an associative array.");
        }

        $writer->writeCollectionHeader(count($value));
        foreach ($value as $key => $item) {
            $this->writeMappedValue($writer, $field->key, $key, $registry);
            $this->writeMappedValue($writer, $field->element, $item, $registry);
        }
    }

    #[\Override]
    public function deserialize(MemoryPackReader $reader, FieldDefinition $field, FormatterRegistry $registry): array|null
    {
        if ($field->key === null || $field->element === null) {
            throw new \InvalidArgumentException("Dictionary field {$field->name} is missing key or value metadata.");
        }

        $length = $reader->readCollectionHeader();
        if ($length === null) {
            return null;
        }

        $items = [];
        for ($i = 0; $i < $length; $i++) {
            $key = $this->readMappedValue($reader, $field->key, $registry);
            $value = $this->readMappedValue($reader, $field->element, $registry);
            $items[is_int($key) || is_string($key) ? $key : json_encode($key, JSON_THROW_ON_ERROR)] = $value;
        }

        return $items;
    }

    private function writeMappedValue(MemoryPackWriter $writer, FieldDefinition $field, mixed $value, FormatterRegistry $registry): void
    {
        if ($field->type === Type::OBJECT) {
            MemoryPackSerializer::writeMappedObject($writer, $field, $value);
            return;
        }

        $registry->resolve($field)->serialize($writer, $value, $field, $registry);
    }

    private function readMappedValue(MemoryPackReader $reader, FieldDefinition $field, FormatterRegistry $registry): mixed
    {
        if ($field->type === Type::OBJECT) {
            return MemoryPackSerializer::readMappedObject($reader, $field);
        }

        return $registry->resolve($field)->deserialize($reader, $field, $registry);
    }
}
