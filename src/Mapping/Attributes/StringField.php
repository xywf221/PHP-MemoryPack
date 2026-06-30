<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use MemoryPack\Mapping\Type;

final class StringField extends MemoryPackField
{
    public function __construct(bool|null $nullable = null)
    {
        parent::__construct(type: Type::STRING, nullable: $nullable);
    }
}
