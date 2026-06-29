<?php

declare(strict_types=1);

namespace MemoryPack\Formatters;

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\MemoryPackSerializer;
use MemoryPack\Mapping\FieldDefinition;
use MemoryPack\Mapping\Type;

final class ListFormatter implements MemoryPackFormatterInterface
{
    #[\Override]
    public function serialize(MemoryPackWriter $writer, mixed $value, FieldDefinition $field, FormatterRegistry $registry): void
    {
        if ($field->element === null) {
            throw new \InvalidArgumentException("List field {$field->name} is missing element metadata.");
        }
        if ($value === null) {
            if (!$field->nullable) {
                throw new \InvalidArgumentException("Field {$field->name} cannot be null.");
            }
            $writer->writeNullCollection();
            return;
        }
        if (!is_array($value)) {
            throw new \InvalidArgumentException("Field {$field->name} must be an array.");
        }

        $writer->writeCollectionHeader(count($value));
        foreach ($value as $item) {
            if ($field->element->type === Type::OBJECT) {
                MemoryPackSerializer::writeMappedObject($writer, $field->element, $item);
                continue;
            }
            $formatter = $registry->resolve($field->element);
            $formatter->serialize($writer, $item, $field->element, $registry);
        }
    }

    #[\Override]
    public function deserialize(MemoryPackReader $reader, FieldDefinition $field, FormatterRegistry $registry): array|null
    {
        if ($field->element === null) {
            throw new \InvalidArgumentException("List field {$field->name} is missing element metadata.");
        }

        $length = $reader->readCollectionHeader();
        if ($length === null) {
            return null;
        }

        $items = [];
        for ($i = 0; $i < $length; $i++) {
            if ($field->element->type === Type::OBJECT) {
                $items[] = MemoryPackSerializer::readMappedObject($reader, $field->element);
                continue;
            }
            $formatter = $registry->resolve($field->element);
            $items[] = $formatter->deserialize($reader, $field->element, $registry);
        }

        return $items;
    }
}
