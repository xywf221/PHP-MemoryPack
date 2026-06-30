<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

final class ObjectField extends MemoryPackField
{
    public function __construct(string $class, bool|null $nullable = null)
    {
        parent::__construct(nullable: $nullable, class: $class);
    }
}
