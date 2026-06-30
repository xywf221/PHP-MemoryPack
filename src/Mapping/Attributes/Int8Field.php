<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use MemoryPack\Mapping\Type;

final class Int8Field extends MemoryPackField
{
    public function __construct(bool|null $nullable = null)
    {
        parent::__construct(type: Type::INT8, nullable: $nullable);
    }
}
