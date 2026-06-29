<?php

declare(strict_types=1);

namespace MemoryPack\Formatters;

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\Mapping\FieldDefinition;
use MemoryPack\Mapping\Type;

final class PrimitiveFormatter implements MemoryPackFormatterInterface
{
    public function __construct(private readonly string $type)
    {
    }

    public function serialize(MemoryPackWriter $writer, mixed $value, FieldDefinition $field, FormatterRegistry $registry): void
    {
        if ($value === null) {
            throw new \InvalidArgumentException("Field {$field->name} cannot be null.");
        }

        match ($this->type) {
            Type::BOOL => $writer->writeBool((bool) $value),
            Type::UINT8 => $writer->writeUInt8((int) $value),
            Type::INT16 => $writer->writeInt16((int) $value),
            Type::UINT16 => $writer->writeUInt16((int) $value),
            Type::INT32 => $writer->writeInt32((int) $value),
            Type::UINT32 => $writer->writeUInt32((int) $value),
            Type::INT64 => $writer->writeInt64((int) $value),
            Type::FLOAT32 => $writer->writeFloat32((float) $value),
            Type::FLOAT64 => $writer->writeFloat64((float) $value),
            default => throw new \InvalidArgumentException("Unsupported primitive type {$this->type}."),
        };
    }

    public function deserialize(MemoryPackReader $reader, FieldDefinition $field, FormatterRegistry $registry): mixed
    {
        return match ($this->type) {
            Type::BOOL => $reader->readBool(),
            Type::UINT8 => $reader->readUInt8(),
            Type::INT16 => $reader->readInt16(),
            Type::UINT16 => $reader->readUInt16(),
            Type::INT32 => $reader->readInt32(),
            Type::UINT32 => $reader->readUInt32(),
            Type::INT64 => $reader->readInt64(),
            Type::FLOAT32 => $reader->readFloat32(),
            Type::FLOAT64 => $reader->readFloat64(),
            default => throw new \InvalidArgumentException("Unsupported primitive type {$this->type}."),
        };
    }
}
