<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use MemoryPack\Mapping\Type;

final class UInt16Field extends MemoryPackField
{
    public function __construct(bool|null $nullable = null)
    {
        parent::__construct(type: Type::UINT16, nullable: $nullable);
    }
}
