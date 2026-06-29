<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Formatters\Utf16StringFormatter;

#[MemoryPackable]
final class Utf16Payload
{
    #[MemoryPackField(order: 0, formatter: Utf16StringFormatter::class)]
    public string $name;
}
