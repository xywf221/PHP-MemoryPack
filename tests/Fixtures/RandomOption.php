<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\Mapping\MemoryPackableInterface;

final class RandomOption implements MemoryPackableInterface
{
    public int $optionId;

    public int $value;

    public static function of(int $optionId, int $value): self
    {
        $option = new self();
        $option->optionId = $optionId;
        $option->value = $value;

        return $option;
    }

    #[\Override]
    public static function memoryPackSerialize(MemoryPackWriter $writer, object|null $value): void
    {
        if (!$value instanceof self) {
            throw new \InvalidArgumentException('RandomOption can only serialize RandomOption instances.');
        }

        $writer->writeUInt32($value->optionId);
        $writer->writeInt32($value->value);
    }

    #[\Override]
    public static function memoryPackDeserialize(MemoryPackReader $reader): self
    {
        return self::of($reader->readUInt32(), $reader->readInt32());
    }
}
