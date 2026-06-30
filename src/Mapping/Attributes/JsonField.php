<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use MemoryPack\Mapping\Type;

final class JsonField extends MemoryPackField
{
    public function __construct(bool|null $nullable = null)
    {
        parent::__construct(type: Type::JSON, nullable: $nullable);
    }
}
