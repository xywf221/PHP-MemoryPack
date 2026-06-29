<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\Mapping\MemoryPackableInterface;

final class Temperature implements MemoryPackableInterface
{
    public int $celsius;

    public static function of(int $celsius): self
    {
        $temperature = new self();
        $temperature->celsius = $celsius;

        return $temperature;
    }

    #[\Override]
    public static function memoryPackSerialize(MemoryPackWriter $writer, object|null $value): void
    {
        if (!$value instanceof self) {
            throw new \InvalidArgumentException('Temperature can only serialize Temperature instances.');
        }

        $writer->writeInt32($value->celsius);
    }

    #[\Override]
    public static function memoryPackDeserialize(MemoryPackReader $reader): self
    {
        return self::of($reader->readInt32());
    }
}
