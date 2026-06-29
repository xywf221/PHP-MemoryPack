<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use DateTimeImmutable;
use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Type;

#[MemoryPackable]
final class Player
{
    #[MemoryPackField(order: 0, type: Type::INT32)]
    public int $id;

    #[MemoryPackField(order: 1, formatter: ReverseStringFormatter::class)]
    public string $name;

    #[MemoryPackField(order: 2, type: Type::LIST, nullable: true, elementType: Type::INT32)]
    public array|null $scores;

    #[MemoryPackField(order: 3, type: Type::DATETIME, nullable: true, format: 'Y-m-d')]
    public DateTimeImmutable|null $createdAt;
}
