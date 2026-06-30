<?php

declare(strict_types=1);

namespace MemoryPack\Mapping\Attributes;

use MemoryPack\Formatters\DateTimeFormatter;
use MemoryPack\Mapping\Type;

final class DateTimeField extends MemoryPackField
{
    public function __construct(bool|null $nullable = null, string|null $format = null)
    {
        parent::__construct(type: Type::STRING, nullable: $nullable, formatter: new DateTimeFormatter($format));
    }
}
