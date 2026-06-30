<?php

declare(strict_types=1);

namespace MemoryPack\Mapping;

final class Type
{
    public const string BOOL = 'bool';
    public const string INT8 = 'int8';
    public const string UINT8 = 'uint8';
    public const string INT16 = 'int16';
    public const string UINT16 = 'uint16';
    public const string INT32 = 'int32';
    public const string UINT32 = 'uint32';
    public const string INT64 = 'int64';
    public const string FLOAT32 = 'float32';
    public const string FLOAT64 = 'float64';
    public const string STRING = 'string';
    public const string LIST = 'list';
    public const string DICT = 'dict';
    public const string OBJECT = 'object';

    private function __construct()
    {
    }
}
