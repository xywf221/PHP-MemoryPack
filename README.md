# PHP MemoryPack

PHP interoperability layer for the core [Cysharp MemoryPack](https://github.com/Cysharp/MemoryPack) binary format.

MemoryPack is not self-describing. PHP must read and write fields in the same order as the C# `[MemoryPackable]` type.

## Layers

- `MemoryPack\Core`: lowest-level protocol reader and writer.
- `MemoryPack\Formatters`: built-in and custom field serializers.
- `MemoryPack\Mapping`: user-facing schema, attributes, reflection mapping, and type metadata.

The common entry point is `MemoryPack\MemoryPackSerializer`.

Requires PHP 8.4 or newer.

## Manual Mapping

```php
use MemoryPack\Mapping\FieldDefinition;
use MemoryPack\Mapping\Type;
use MemoryPack\MemoryPackSerializer;

$schema = [
    FieldDefinition::of('id', Type::INT32),
    FieldDefinition::of('name', Type::STRING),
    FieldDefinition::listOf('scores', FieldDefinition::of('score', Type::INT32), nullable: true),
];

$payload = MemoryPackSerializer::serialize($schema, [
    'id' => 7,
    'name' => '雷少',
    'scores' => [10, 20, 30],
]);

$value = MemoryPackSerializer::deserialize($schema, $payload);
```

## Attribute Mapping

```php
use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Type;
use MemoryPack\MemoryPackSerializer;

#[MemoryPackable]
final class Player
{
    #[MemoryPackField(order: 0, type: Type::INT32)]
    public int $id;

    #[MemoryPackField(order: 1)]
    public string $name;

    #[MemoryPackField(order: 2, type: Type::LIST, nullable: true, elementType: Type::INT32)]
    public array|null $scores;
}

$payload = MemoryPackSerializer::serializeObject($player);
$player = MemoryPackSerializer::deserializeObject(Player::class, $payload);
```

## Nested Objects

```php
#[MemoryPackable]
final class A
{
    #[MemoryPackField(order: 0)]
    public B $b;
}

#[MemoryPackable]
final class B
{
    #[MemoryPackField(order: 0)]
    public C $c;
}
```

Nested mappings recurse, so `A -> B -> C` is serialized in field order.

For `list<object>`, provide the element class:

```php
#[MemoryPackField(order: 0, type: Type::LIST, elementClass: Point::class)]
public array $points;
```

## C# Struct / Value Type

C# structs are value types and are encoded without a nullable object header when nested. Mark the mapped PHP class as `valueType`; fields only reference the class.

```php
#[MemoryPackable(valueType: true)]
final class Point
{
    #[MemoryPackField(order: 0, type: Type::INT32)]
    public int $x;

    #[MemoryPackField(order: 1, type: Type::INT32)]
    public int $y;
}

#[MemoryPackable]
final class Shape
{
    #[MemoryPackField(order: 0)]
    public Point $origin;

    #[MemoryPackField(order: 1, type: Type::LIST, elementClass: Point::class)]
    public array $points;
}
```


## Arrays, Lists, and Dictionaries

C# `T[]` and `List<T>` use different formatter classes internally, but for normal element serialization their wire shape is the same: collection header followed by each item in order. PHP maps both to `Type::LIST`.

C# `Dictionary<TKey, TValue>` is encoded as collection header followed by key/value pairs. PHP maps this to `Type::DICT` and associative arrays.

```php
use MemoryPack\Mapping\FieldDefinition;
use MemoryPack\Mapping\Type;

$schema = [
    FieldDefinition::dictOf(
        'scores',
        FieldDefinition::of('key', Type::STRING),
        FieldDefinition::of('value', Type::INT32),
    ),
];
```

Attribute mapping:

```php
#[MemoryPackField(order: 0, type: Type::DICT, keyType: Type::STRING, elementType: Type::INT32)]
public array $scores;
```
For dictionary object keys or values, provide `keyClass` or `elementClass`. Whether that class is encoded as a C# struct comes from its own `#[MemoryPackable(valueType: true)]` declaration.

## String Encoding

The default string format is UTF-8, matching the standard C# MemoryPack string behavior. PHP core `MemoryPackReader` and `MemoryPackWriter` use this format for `Type::STRING`.

UTF-8 strings are written as:

1. `~utf8ByteLength`
2. `0`
3. raw UTF-8 bytes

Use `Utf16StringFormatter` only when a field is explicitly marked for UTF-16 interop. In PHP, apply `->withFormatter(Utf16StringFormatter::class)` or `formatter: Utf16StringFormatter::class`; in C#, apply `[Utf16StringFormatter]` on the field.

## Custom Formatter

```php
use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\Formatters\FormatterRegistry;
use MemoryPack\Formatters\MemoryPackFormatterInterface;
use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\FieldDefinition;

final class UpperNameFormatter implements MemoryPackFormatterInterface
{
    public function serialize(MemoryPackWriter $writer, mixed $value, FieldDefinition $field, FormatterRegistry $registry): void
    {
        $writer->writeString(strtoupper((string) $value));
    }

    public function deserialize(MemoryPackReader $reader, FieldDefinition $field, FormatterRegistry $registry): mixed
    {
        return strtolower((string) $reader->readString());
    }
}

final class Player
{
    #[MemoryPackField(order: 0, formatter: UpperNameFormatter::class)]
    public string $name;
}

$player = new Player();
$player->name = 'abc';

$payload = MemoryPackSerializer::serializeObject($player);
$player = MemoryPackSerializer::deserializeObject(Player::class, $payload);
```

## Self-Serializing Types

When a class is referenced by many fields and you do not want to attach a custom formatter to each of them, let the class own its wire format by implementing `MemoryPackableInterface`. The serializer delegates to its static methods at every object boundary: the top level, nested object fields, list elements, and dictionary values. This mirrors C#'s `IMemoryPackable<T>`.

The two methods have full control of the bytes, including the object or null header. Mark them with `#[\Override]`.

```php
use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\Mapping\MemoryPackableInterface;

final class Temperature implements MemoryPackableInterface
{
    public int $celsius;

    public static function memoryPackSerialize(MemoryPackWriter $writer, object|null $value): void
    {
        $writer->writeInt32($value->celsius);
    }

    public static function memoryPackDeserialize(MemoryPackReader $reader): self
    {
        $temperature = new self();
        $temperature->celsius = $reader->readInt32();

        return $temperature;
    }
}
```

Any field typed as `Temperature`, or a list or dictionary of `Temperature`, now uses these methods automatically with no per-field configuration.

To supply only a `Schema` and reuse the default wire format, implement `MemoryPackSchemaInterface` with `memoryPackSchema(): Schema` instead.

## Supported Types

- `bool`
- `uint8`
- `int16`
- `uint16`
- `int32`
- `uint32`
- `int64`
- `float32`
- `float64`
- `string`
- `list`
- `dict`
- `datetime`
- `json`
- `object`

Current boundary: this is not a full C# source-generator port. It does not yet implement unions, circular reference tracking, version-tolerant object headers, unmanaged array fast paths, dictionaries, GUIDs, or the full MemoryPack formatter ecosystem.
