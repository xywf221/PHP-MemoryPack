<?php

declare(strict_types=1);

namespace MemoryPack;

use MemoryPack\Core\MemoryPackReader;
use MemoryPack\Core\MemoryPackWriter;
use MemoryPack\Mapping\FieldDefinition;
use MemoryPack\Mapping\Schema;
use MemoryPack\Mapping\SchemaFactory;
use MemoryPack\Mapping\Type;
use MemoryPack\Exception\MemoryPackException;
use MemoryPack\Formatters\FormatterRegistry;
use MemoryPack\Formatters\MemoryPackFormatterInterface;
use MemoryPack\Mapping\MemoryPackableInterface;
use ReflectionClass;

final class MemoryPackSerializer
{
    public const string BOOL = Type::BOOL;
    public const string INT8 = Type::INT8;
    public const string UINT8 = Type::UINT8;
    public const string INT16 = Type::INT16;
    public const string UINT16 = Type::UINT16;
    public const string INT32 = Type::INT32;
    public const string UINT32 = Type::UINT32;
    public const string INT64 = Type::INT64;
    public const string FLOAT32 = Type::FLOAT32;
    public const string FLOAT64 = Type::FLOAT64;
    public const string STRING = Type::STRING;
    public const string LIST = Type::LIST;
    public const string DICT = Type::DICT;
    public const string DATETIME = Type::DATETIME;
    public const string JSON = Type::JSON;
    public const string OBJECT = Type::OBJECT;

    private static FormatterRegistry|null $registry = null;
    private static SchemaFactory|null $schemaFactory = null;

    public static function registry(): FormatterRegistry
    {
        return self::$registry ??= new FormatterRegistry();
    }

    public static function schemaFactory(): SchemaFactory
    {
        return self::$schemaFactory ??= new SchemaFactory();
    }

    public static function registerFormatter(string $type, MemoryPackFormatterInterface $formatter): void
    {
        self::registry()->register($type, $formatter);
    }

    /**
     * @param list<FieldDefinition>|Schema $schema
     * @param array<string, mixed>|object|null $value
     */
    public static function serialize(array|Schema $schema, array|object|null $value): string
    {
        $schema = self::normalizeSchema($schema);
        $writer = new MemoryPackWriter();
        self::writeObject($writer, $schema, $value, !$schema->valueType);

        return $writer->bytes();
    }

    /**
     * @param list<FieldDefinition>|Schema $schema
     * @return array<string, mixed>|object|null
     */
    public static function deserialize(array|Schema $schema, string $payload): array|object|null
    {
        $schema = self::normalizeSchema($schema);
        $reader = new MemoryPackReader($payload);
        $value = self::readObject($reader, $schema, !$schema->valueType);
        if ($reader->remaining() !== 0) {
            throw new MemoryPackException('Payload has trailing bytes.');
        }

        return $schema->className === null || $value === null ? $value : self::hydrate($schema->className, $value);
    }

    public static function serializeObject(object|null $value): string
    {
        if ($value !== null && $value instanceof MemoryPackableInterface) {
            $writer = new MemoryPackWriter();
            $value::memoryPackSerialize($writer, $value);
            return $writer->bytes();
        }

        if ($value === null) {
            $writer = new MemoryPackWriter();
            $writer->writeNullObject();
            return $writer->bytes();
        }

        return self::serialize(self::schemaFactory()->create($value::class), $value);
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T|null
     */
    public static function deserializeObject(string $className, string $payload): object|null
    {
        if (is_subclass_of($className, MemoryPackableInterface::class)) {
            $reader = new MemoryPackReader($payload);
            $value = $className::memoryPackDeserialize($reader);
            if ($reader->remaining() !== 0) {
                throw new MemoryPackException('Payload has trailing bytes.');
            }

            return $value instanceof $className ? $value : null;
        }

        $value = self::deserialize(self::schemaFactory()->create($className), $payload);

        return $value instanceof $className ? $value : null;
    }

    public static function writeMappedObject(MemoryPackWriter $writer, FieldDefinition $field, mixed $value): void
    {
        self::writeNestedObject($writer, $field, $value);
    }

    public static function readMappedObject(MemoryPackReader $reader, FieldDefinition $field): object|array|null
    {
        return self::readNestedObject($reader, $field);
    }

    /**
     * Write a single nested object onto an existing writer, mirroring C#'s
     * MemoryPackWriter.WritePackable. The target class may implement
     * MemoryPackableInterface (its own wire format is used) or rely on the
     * default schema mapping. Use this inside memoryPackSerialize.
     *
     * @param class-string|null $className defaults to $value::class
     */
    public static function writePackable(MemoryPackWriter $writer, object|null $value, string|null $className = null, bool $nullable = true): void
    {
        $className ??= $value !== null ? $value::class : null;
        if ($className === null) {
            throw new \InvalidArgumentException('writePackable needs a value or an explicit class name.');
        }

        self::writeNestedObject($writer, new FieldDefinition('packable', Type::OBJECT, $nullable, null, null, null, null, $className), $value);
    }

    /**
     * Read a single nested object from an existing reader, mirroring C#'s
     * MemoryPackReader.ReadPackable. Use this inside memoryPackDeserialize.
     *
     * @template T of object
     * @param class-string<T> $className
     * @return T|null
     */
    public static function readPackable(MemoryPackReader $reader, string $className, bool $nullable = true): object|null
    {
        $value = self::readNestedObject($reader, new FieldDefinition('packable', Type::OBJECT, $nullable, null, null, null, null, $className));

        return $value instanceof $className ? $value : null;
    }

    private static function writeObject(MemoryPackWriter $writer, Schema $schema, array|object|null $value, bool $writeHeader): void
    {
        if ($value === null) {
            if (!$writeHeader) {
                throw new \InvalidArgumentException("Value type {$schema->className} cannot be null.");
            }
            $writer->writeNullObject();
            return;
        }

        if ($writeHeader) {
            $writer->writeObjectHeader(count($schema->fields));
        }
        foreach ($schema->fields as $field) {
            self::writeField($writer, $field, self::readMember($value, $field));
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function readObject(MemoryPackReader $reader, Schema $schema, bool $readHeader): array|null
    {
        $memberCount = count($schema->fields);
        if ($readHeader) {
            $memberCount = $reader->readObjectHeader();
            if ($memberCount === null) {
                return null;
            }
        }
        if ($memberCount > count($schema->fields)) {
            throw new MemoryPackException('Payload has more members than the schema can read.');
        }

        $result = [];
        foreach ($schema->fields as $index => $field) {
            $result[$field->propertyName ?? $field->name] = $index < $memberCount
                ? self::readField($reader, $field)
                : null;
        }

        return $result;
    }

    private static function writeField(MemoryPackWriter $writer, FieldDefinition $field, mixed $value): void
    {
        if ($field->type === Type::OBJECT) {
            self::writeNestedObject($writer, $field, $value);
            return;
        }

        $formatter = self::formatterFor($field);
        $formatter->serialize($writer, $value, $field, self::registry());
    }

    private static function readField(MemoryPackReader $reader, FieldDefinition $field): mixed
    {
        if ($field->type === Type::OBJECT) {
            return self::readNestedObject($reader, $field);
        }

        $formatter = self::formatterFor($field);

        return $formatter->deserialize($reader, $field, self::registry());
    }

    private static function writeNestedObject(MemoryPackWriter $writer, FieldDefinition $field, mixed $value): void
    {
        if ($value === null && !$field->nullable) {
            throw new \InvalidArgumentException("Field {$field->name} cannot be null.");
        }
        if ($value === null) {
            $writer->writeNullObject();
            return;
        }
        if ($field->className === null) {
            throw new \InvalidArgumentException("Object field {$field->name} needs a class name.");
        }
        if (is_subclass_of($field->className, MemoryPackableInterface::class)) {
            $field->className::memoryPackSerialize($writer, $value);
            return;
        }

        $schema = self::schemaFactory()->create($field->className);
        self::writeObject($writer, $schema, $value, !$field->valueType && !$schema->valueType);
    }

    private static function readNestedObject(MemoryPackReader $reader, FieldDefinition $field): object|array|null
    {
        if ($field->className === null) {
            throw new \InvalidArgumentException("Object field {$field->name} needs a class name.");
        }
        if (is_subclass_of($field->className, MemoryPackableInterface::class)) {
            return $field->className::memoryPackDeserialize($reader);
        }

        $schema = self::schemaFactory()->create($field->className);
        $value = self::readObject($reader, $schema, !$field->valueType && !$schema->valueType);

        return $value === null ? null : self::hydrate($field->className, $value);
    }

    private static function formatterFor(FieldDefinition $field): MemoryPackFormatterInterface
    {
        return self::registry()->resolve($field);
    }

    /**
     * @param list<FieldDefinition>|Schema $schema
     */
    private static function normalizeSchema(array|Schema $schema): Schema
    {
        return $schema instanceof Schema ? $schema : new Schema($schema);
    }

    private static function readMember(array|object $value, FieldDefinition $field): mixed
    {
        $name = $field->propertyName ?? $field->name;
        if (is_array($value)) {
            return $value[$name] ?? $value[$field->name] ?? null;
        }

        return $value->{$name} ?? null;
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $values
     */
    private static function hydrate(string $className, array $values): object
    {
        $class = new ReflectionClass($className);
        $object = $class->newInstanceWithoutConstructor();
        foreach ($values as $name => $value) {
            if (!$class->hasProperty($name)) {
                continue;
            }
            $property = $class->getProperty($name);
            $property->setValue($object, $value);
        }

        return $object;
    }
}
