<?php

declare(strict_types=1);

namespace MemoryPack\Formatters;

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\Mapping\FieldDefinition;

final class Utf16StringFormatter implements MemoryPackFormatterInterface
{
    #[\Override]
    public function serialize(MemoryPackWriter $writer, mixed $value, FieldDefinition $field, FormatterRegistry $registry): void
    {
        if ($value === null && !$field->nullable) {
            throw new \InvalidArgumentException("Field {$field->name} cannot be null.");
        }

        if ($value === null) {
            $writer->writeNullString();
            return;
        }

        if ($value === '') {
            $writer->writeInt32(0);
            return;
        }

        $encoded = $this->encodeUtf16Le((string) $value);
        $writer->writeInt32(intdiv(strlen($encoded), 2));
        $writer->writeRaw($encoded);
    }

    #[\Override]
    public function deserialize(MemoryPackReader $reader, FieldDefinition $field, FormatterRegistry $registry): mixed
    {
        $length = $reader->readInt32();
        if ($length === -1) {
            return null;
        }
        if ($length === 0) {
            return '';
        }

        return $this->decodeUtf16Le($reader->readRaw($length * 2));
    }

    private function encodeUtf16Le(string $value): string
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($value, 'UTF-16LE', 'UTF-8');
        }
        if (function_exists('iconv')) {
            $encoded = iconv('UTF-8', 'UTF-16LE', $value);
            if ($encoded !== false) {
                return $encoded;
            }
        }

        throw new \RuntimeException('Writing UTF-16 strings requires mbstring or iconv.');
    }

    private function decodeUtf16Le(string $bytes): string
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($bytes, 'UTF-8', 'UTF-16LE');
        }
        if (function_exists('iconv')) {
            $value = iconv('UTF-16LE', 'UTF-8', $bytes);
            if ($value !== false) {
                return $value;
            }
        }

        throw new \RuntimeException('Reading UTF-16 strings requires mbstring or iconv.');
    }
}
