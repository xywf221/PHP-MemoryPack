<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use MemoryPack\Mapping\Type;

final class DictField extends MemoryPackField
{
    public function __construct(MemoryPackField $key, MemoryPackField $element, bool|null $nullable = null)
    {
        parent::__construct(type: Type::DICT, nullable: $nullable, element: $element, key: $key);
    }
}
