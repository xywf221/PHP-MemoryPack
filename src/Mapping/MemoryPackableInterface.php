<?php

declare(strict_types=1);

namespace MemoryPack\Mapping;

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;

/**
 * A type that owns its full MemoryPack wire format, including the object or
 * null header. Use this when a class is referenced by many fields and you do
 * not want to attach a custom formatter to each of them: the serializer
 * delegates to these methods at every object boundary (top level, nested
 * objects, list elements, and dictionary values).
 */
interface MemoryPackableInterface
{
    public static function memoryPackSerialize(MemoryPackWriter $writer, object|null $value): void;

    public static function memoryPackDeserialize(MemoryPackReader $reader): object|null;
}
