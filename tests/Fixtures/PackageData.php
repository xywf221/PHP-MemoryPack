<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Attributes\ListField;
use MemoryPack\Mapping\Attributes\ObjectField;
use MemoryPack\Mapping\Type;

#[MemoryPackable]
final class PackageData
{
    #[MemoryPackField(
        order: 0,
        type: Type::LIST,
        element: new ListField(new ObjectField(PackageItem::class)),
    )]
    public array $selections;
}
