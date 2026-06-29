<?php

declare(strict_types=1);

namespace MemoryPack\Core;

use MemoryPack\Exception\MemoryPackException;

final class MemoryPackReader
{
    private int $offset = 0;

    public function __construct(private readonly string $buffer)
    {
    }

    public function remaining(): int
    {
        return strlen($this->buffer) - $this->offset;
    }

    public function readObjectHeader(): int|null
    {
        $memberCount = $this->readUInt8();

        return $memberCount === 0xff ? null : $memberCount;
    }

    public function readCollectionHeader(): int|null
    {
        $length = $this->readInt32();

        return $length === -1 ? null : $length;
    }

    public function readString(): string|null
    {
        $length = $this->readInt32();
        if ($length === -1) {
            return null;
        }
        if ($length === 0) {
            return '';
        }

        $utf8Length = ~$length;
        $this->readInt32();

        return $this->readRaw($utf8Length);
    }

    public function readBool(): bool
    {
        return $this->readUInt8() !== 0;
    }

    public function readUInt8(): int
    {
        return unpack('C', $this->readRaw(1))[1];
    }

    public function readInt16(): int
    {
        $value = unpack('v', $this->readRaw(2))[1];

        return $value >= 0x8000 ? $value - 0x10000 : $value;
    }

    public function readUInt16(): int
    {
        return unpack('v', $this->readRaw(2))[1];
    }

    public function readInt32(): int
    {
        $value = unpack('V', $this->readRaw(4))[1];

        return $value >= 0x80000000 ? $value - 0x100000000 : $value;
    }

    public function readUInt32(): int
    {
        return unpack('V', $this->readRaw(4))[1];
    }

    public function readInt64(): int
    {
        $parts = unpack('Vlow/Vhigh', $this->readRaw(8));
        if ($parts['high'] < 0x80000000) {
            return ($parts['high'] << 32) | $parts['low'];
        }

        if ($parts['high'] === 0x80000000 && $parts['low'] === 0) {
            return PHP_INT_MIN;
        }

        $magnitude = (((~$parts['high']) & 0x7fffffff) << 32) | ((~$parts['low']) & 0xffffffff);

        return -$magnitude - 1;
    }

    public function readFloat32(): float
    {
        return unpack('g', $this->readRaw(4))[1];
    }

    public function readFloat64(): float
    {
        return unpack('e', $this->readRaw(8))[1];
    }

    public function readRaw(int $length): string
    {
        if ($length < 0) {
            throw new \InvalidArgumentException('Length must be non-negative.');
        }
        if ($this->remaining() < $length) {
            throw new MemoryPackException('Unexpected end of MemoryPack payload.');
        }

        $bytes = substr($this->buffer, $this->offset, $length);
        $this->offset += $length;

        return $bytes;
    }
}
