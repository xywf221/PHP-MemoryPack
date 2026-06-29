<?php

declare(strict_types=1);

namespace MemoryPack\Mapping;

interface MemoryPackableInterface
{
    public static function memoryPackSchema(): Schema;
}
