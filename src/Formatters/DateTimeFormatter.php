<?php

declare(strict_types=1);

namespace MemoryPack\Formatters;

use DateTimeImmutable;
use DateTimeInterface;
use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\Mapping\FieldDefinition;

final class DateTimeFormatter implements MemoryPackFormatterInterface
{
    #[\Override]
    public function serialize(MemoryPackWriter $writer, mixed $value, FieldDefinition $field, FormatterRegistry $registry): void
    {
        if ($value === null && !$field->nullable) {
            throw new \InvalidArgumentException("Field {$field->name} cannot be null.");
        }
        if ($value !== null && !$value instanceof DateTimeInterface) {
            throw new \InvalidArgumentException("Field {$field->name} must be a DateTimeInterface.");
        }

        $writer->writeString($value?->format($field->format ?? DateTimeInterface::ATOM));
    }

    #[\Override]
    public function deserialize(MemoryPackReader $reader, FieldDefinition $field, FormatterRegistry $registry): DateTimeImmutable|null
    {
        $value = $reader->readString();

        return $value === null ? null : new DateTimeImmutable($value);
    }
}
