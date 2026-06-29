<?php

declare(strict_types=1);

namespace MemoryPack\Core;

final class MemoryPackWriter
{
    private string $buffer = '';

    public function bytes(): string
    {
        return $this->buffer;
    }

    public function writeNullObject(): void
    {
        $this->writeUInt8(0xff);
    }

    public function writeObjectHeader(int $memberCount): void
    {
        if ($memberCount < 0 || $memberCount > 0xf9) {
            throw new \InvalidArgumentException('Object member count must be between 0 and 249.');
        }

        $this->writeUInt8($memberCount);
    }

    public function writeNullCollection(): void
    {
        $this->writeInt32(-1);
    }

    public function writeCollectionHeader(int $length): void
    {
        if ($length < 0) {
            throw new \InvalidArgumentException('Collection length must be non-negative.');
        }

        $this->writeInt32($length);
    }

    public function writeNullString(): void
    {
        $this->writeInt32(-1);
    }

    public function writeString(string|null $value): void
    {
        if ($value === null) {
            $this->writeNullString();
            return;
        }

        if ($value === '') {
            $this->writeInt32(0);
            return;
        }

        $this->writeInt32(~strlen($value));
        $this->writeInt32(0);
        $this->writeRaw($value);
    }

    public function writeBool(bool $value): void
    {
        $this->writeUInt8($value ? 1 : 0);
    }

    public function writeUInt8(int $value): void
    {
        if ($value < 0 || $value > 0xff) {
            throw new \InvalidArgumentException('UInt8 value is out of range.');
        }

        $this->buffer .= pack('C', $value);
    }

    public function writeInt16(int $value): void
    {
        $this->buffer .= pack('v', $value & 0xffff);
    }

    public function writeUInt16(int $value): void
    {
        if ($value < 0 || $value > 0xffff) {
            throw new \InvalidArgumentException('UInt16 value is out of range.');
        }

        $this->buffer .= pack('v', $value);
    }

    public function writeInt32(int $value): void
    {
        $this->buffer .= pack('V', $value & 0xffffffff);
    }

    public function writeUInt32(int $value): void
    {
        if ($value < 0 || $value > 0xffffffff) {
            throw new \InvalidArgumentException('UInt32 value is out of range.');
        }

        $this->buffer .= pack('V', $value);
    }

    public function writeInt64(int $value): void
    {
        $low = $value & 0xffffffff;
        $high = ($value >> 32) & 0xffffffff;
        $this->buffer .= pack('V2', $low, $high);
    }

    public function writeFloat32(float $value): void
    {
        $this->buffer .= pack('g', $value);
    }

    public function writeFloat64(float $value): void
    {
        $this->buffer .= pack('e', $value);
    }

    public function writeRaw(string $bytes): void
    {
        $this->buffer .= $bytes;
    }

}
