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
use MemoryPack\Mapping\Attributes\Int32Field;
use MemoryPack\Mapping\Type;
use MemoryPack\MemoryPackSerializer;

#[MemoryPackable]
final class Player
{
    #[MemoryPackField(order: 0, type: Type::INT32)]
    public int $id;

    #[MemoryPackField(order: 1)]
    public string $name;

    #[MemoryPackField(order: 2, type: Type::LIST, nullable: true, element: new Int32Field())]
    public array|null $scores;
}

$payload = MemoryPackSerializer::serializeObject($player);
$player = MemoryPackSerializer::deserializeObject(Player::class, $payload);
```

### Field Metadata Helpers

PHP attributes cannot call static methods, so use the short `new XxxField()` classes inside `#[MemoryPackField(...)]`:

```php
use MemoryPack\Mapping\Attributes\DictField;
use MemoryPack\Mapping\Attributes\Int32Field;
use MemoryPack\Mapping\Attributes\StringField;

#[MemoryPackField(order: 0, type: Type::DICT, key: new StringField(), element: new Int32Field())]
public array $scores;
```

For runtime metadata construction, use `MemoryPackField::xxOf()` helpers:

```php
use MemoryPack\Mapping\Attributes\MemoryPackField;

$field = MemoryPackField::dictOf(
    MemoryPackField::stringOf(),
    MemoryPackField::int32Of(),
);
```

Built-in helpers include `boolOf`, `int8Of`, `uint8Of`, `int16Of`, `uint16Of`, `int32Of`, `uint32Of`, `int64Of`, `float32Of`, `float64Of`, `stringOf`, `dateTimeOf`, `jsonOf`, `objectOf`, `listOf`, and `dictOf`.

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
#[MemoryPackField(order: 0, type: Type::LIST, element: new ObjectField(Point::class))]
public array $points;
```

For nested lists, use the recursive `element` form. This mirrors nested `FieldDefinition::listOf()` and can express more than two dimensions.

```php
use MemoryPack\Mapping\Attributes\ListField;
use MemoryPack\Mapping\Attributes\ObjectField;

#[MemoryPackField(
    order: 0,
    type: Type::LIST,
    element: new ListField(new ObjectField(PackageItem::class)),
)]
public array $selections; // PackageItem[][]
```

Manual schemas can express the same shape by nesting `FieldDefinition::listOf()`:

```php
$schema = [
    FieldDefinition::listOf(
        'selections',
        FieldDefinition::listOf('row', FieldDefinition::of('item', Type::INT32)),
    ),
];
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

    #[MemoryPackField(order: 1, type: Type::LIST, element: new ObjectField(Point::class))]
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
use MemoryPack\Mapping\Attributes\Int32Field;
use MemoryPack\Mapping\Attributes\StringField;

#[MemoryPackField(
    order: 0,
    type: Type::DICT,
    key: new StringField(),
    element: new Int32Field(),
)]
public array $scores;
```
For dictionary object keys or values, use `new ObjectField(SomeClass::class)`. Whether that class is encoded as a C# struct comes from its own `#[MemoryPackable(valueType: true)]` declaration.

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

### Nesting Packables

Inside `memoryPackSerialize`/`memoryPackDeserialize` you often write a run of scalars and then nest another object, like C#'s `writer.WriteUnmanaged(...)` followed by `writer.WritePackable(child)`. Scalars map to the writer/reader primitives directly. For the nested object, use `MemoryPackSerializer::writePackable` / `readPackable`, which mirror C#'s `WritePackable<T>` / `ReadPackable<T>`. They resolve the target class automatically: if it implements `MemoryPackableInterface` its own wire format is used, otherwise the default schema mapping applies.

```php
use MemoryPack\MemoryPackSerializer;

final class EquipmentItem implements MemoryPackableInterface
{
    public int $itemId;
    public bool $sealed;
    public RandomOption $randomOption;

    public static function memoryPackSerialize(MemoryPackWriter $writer, object|null $value): void
    {
        // unmanaged scalars: no object header
        $writer->writeUInt32($value->itemId);
        $writer->writeBool($value->sealed);

        // nested packable on the same writer
        MemoryPackSerializer::writePackable($writer, $value->randomOption);
    }

    public static function memoryPackDeserialize(MemoryPackReader $reader): self
    {
        $item = new self();
        $item->itemId = $reader->readUInt32();
        $item->sealed = $reader->readBool();
        $item->randomOption = MemoryPackSerializer::readPackable($reader, RandomOption::class);

        return $item;
    }
}
```

To supply only a `Schema` and reuse the default wire format, implement `MemoryPackSchemaInterface` with `memoryPackSchema(): Schema` instead.

## Editing Payloads / Schema Migration

Appending a `nullable` field at the end of an object is backward compatible: the object header records a member count, so a reader simply leaves trailing new fields null. Keep wire `order` append-only and never change an existing field's order or type, and old data keeps reading.

When that is not enough — changing a field's type, removing a middle field, inserting a field ahead of an existing one — use `PayloadEditor`. It decodes a payload into a mutable tree using the schema the bytes were written with, lets you edit the structure, and re-encodes from the edited tree. Re-encoding never re-derives schema from a class, so structural edits survive.

```php
use MemoryPack\Editor\PayloadEditor;
use MemoryPack\Mapping\FieldDefinition;
use MemoryPack\Mapping\Type;

$editor = new PayloadEditor($payload, $oldSchema);

// read
$editor->getValue('b.c');                 // dotted path descends object fields
$editor->has('b.c');

// change values
$editor->setValue('b.c', 5);
$editor->replace('b.c', fn ($old) => $old + 1);

// change structure
$editor->addProperty('b', FieldDefinition::of('d', Type::INT32), value: 0, order: 0); // insert ahead of b's fields
$editor->removeProperty('b.c');
$editor->setOrder('b.c', 0);              // move to a new 0-based wire position

$newBytes = $editor->toBytes();           // re-encode from the edited tree
$tree = $editor->toArray();               // whole tree as nested arrays
```

Paths are dot-separated and descend through object fields; pass `''` for the root object. The `order` argument is the 0-based position in the object's field list, which is exactly the wire order. To change a field's type, remove it and re-add it at the same `order` with a new `FieldDefinition`.

Notes:

- `list` and `dict` fields are edited as whole values (`getValue` returns the array, `setValue` writes it back); the path does not descend into collection elements.
- A field whose class implements `MemoryPackableInterface` is opaque: its decoded instance is kept and re-serialized through the same class. You can replace it wholesale but not descend into it.
- Decode the payload with the schema it was actually written with. For a versioned store, prefix each payload with a version byte and pick the matching old schema before editing.

## Supported Types

- `bool`
- `int8`
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
