<?php

declare(strict_types=1);

namespace MemoryPack\Formatters;

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\Mapping\FieldDefinition;

interface MemoryPackFormatterInterface
{
    public function serialize(MemoryPackWriter $writer, mixed $value, FieldDefinition $field, FormatterRegistry $registry): void;

    public function deserialize(MemoryPackReader $reader, FieldDefinition $field, FormatterRegistry $registry): mixed;
}
