<?php

declare(strict_types=1);

namespace MemoryPack\Tests\Fixtures;

use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Attributes\ObjectField;
use MemoryPack\Mapping\Attributes\StringField;
use MemoryPack\Mapping\Type;

#[MemoryPackable]
final class Forecast
{
    #[MemoryPackField(order: 0)]
    public Temperature $current;

    #[MemoryPackField(order: 1, type: Type::LIST, element: new ObjectField(Temperature::class))]
    public array $hourly;

    #[MemoryPackField(order: 2, type: Type::DICT, key: new StringField(), element: new ObjectField(Temperature::class))]
    public array $byCity;
}
