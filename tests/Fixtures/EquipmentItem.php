<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\MemoryPackSerializer;
use MemoryPack\Mapping\MemoryPackableInterface;

/**
 * Mirrors a C# IMemoryPackable<T> that writes a run of unmanaged scalars and
 * then nests another packable via WritePackable/ReadPackable.
 */
final class EquipmentItem implements MemoryPackableInterface
{
    public int $itemId;
    public bool $sealed;
    public int $durability;
    public RandomOption $randomOption;

    #[\Override]
    public static function memoryPackSerialize(MemoryPackWriter $writer, object|null $value): void
    {
        if (!$value instanceof self) {
            throw new \InvalidArgumentException('EquipmentItem can only serialize EquipmentItem instances.');
        }

        // WriteUnmanaged: a run of scalars with no object header.
        $writer->writeUInt32($value->itemId);
        $writer->writeBool($value->sealed);
        $writer->writeUInt16($value->durability);

        // WritePackable: a nested packable on the same writer.
        MemoryPackSerializer::writePackable($writer, $value->randomOption);
    }

    #[\Override]
    public static function memoryPackDeserialize(MemoryPackReader $reader): self
    {
        $item = new self();
        $item->itemId = $reader->readUInt32();
        $item->sealed = $reader->readBool();
        $item->durability = $reader->readUInt16();
        $item->randomOption = MemoryPackSerializer::readPackable($reader, RandomOption::class);

        return $item;
    }
}
