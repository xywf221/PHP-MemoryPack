<?php

declare(strict_types=1);

namespace MemoryPack\Mapping;

final class Type
{
    public const BOOL = 'bool';
    public const UINT8 = 'uint8';
    public const INT16 = 'int16';
    public const UINT16 = 'uint16';
    public const INT32 = 'int32';
    public const UINT32 = 'uint32';
    public const INT64 = 'int64';
    public const FLOAT32 = 'float32';
    public const FLOAT64 = 'float64';
    public const STRING = 'string';
    public const LIST = 'list';
    public const DICT = 'dict';
    public const DATETIME = 'datetime';
    public const JSON = 'json';
    public const OBJECT = 'object';

    private function __construct()
    {
    }
}
