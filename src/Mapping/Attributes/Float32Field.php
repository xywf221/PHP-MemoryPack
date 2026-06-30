<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use MemoryPack\Mapping\Type;

final class Float32Field extends MemoryPackField
{
    public function __construct(bool|null $nullable = null)
    {
        parent::__construct(type: Type::FLOAT32, nullable: $nullable);
    }
}
