<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use MemoryPack\Mapping\Type;

final class ListField extends MemoryPackField
{
    public function __construct(MemoryPackField $element, bool|null $nullable = null)
    {
        parent::__construct(type: Type::LIST, nullable: $nullable, element: $element);
    }
}
