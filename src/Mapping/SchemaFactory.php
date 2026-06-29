<?php

declare(strict_types=1);

namespace MemoryPack\Mapping;

use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackFormatter;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

final class SchemaFactory
{
    /**
     * @var array<class-string, Schema>
     */
    private array $cache = [];

    /**
     * @param class-string $className
     */
    public function create(string $className): Schema
    {
        if (isset($this->cache[$className])) {
            return $this->cache[$className];
        }
        if (is_subclass_of($className, MemoryPackableInterface::class)) {
            return $this->cache[$className] = $className::memoryPackSchema();
        }

        $class = new ReflectionClass($className);
        $memoryPackable = $this->classAttribute($class, MemoryPackable::class);
        $fields = [];
        $index = 0;

        foreach ($class->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $fieldAttribute = $this->propertyAttribute($property, MemoryPackField::class);
            if (!$property->isPublic() && $fieldAttribute === null) {
                continue;
            }

            $fields[] = [
                'order' => $fieldAttribute?->order ?? $index,
                'field' => $this->createField($property, $fieldAttribute),
            ];
            $index++;
        }

        usort($fields, static fn (array $left, array $right): int => $left['order'] <=> $right['order']);

        return $this->cache[$className] = new Schema(array_column($fields, 'field'), $className, $memoryPackable?->valueType ?? false);
    }

    private function createField(ReflectionProperty $property, MemoryPackField|null $attribute): FieldDefinition
    {
        $formatterAttribute = $this->propertyAttribute($property, MemoryPackFormatter::class);
        $className = $attribute?->class ?? $this->objectClass($property);
        $type = $this->declaredType($property, $attribute, $className);
        $className = $type === Type::OBJECT ? $className : null;

        return new FieldDefinition(
            $property->getName(),
            $type,
            $attribute?->nullable ?? $this->allowsNull($property),
            $this->elementDefinition($property, $attribute),
            $this->keyDefinition($property, $attribute),
            $attribute?->formatter ?? $formatterAttribute?->formatterClass,
            $attribute?->format,
            $className,
            $this->isValueType($className),
            $property->getName(),
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    private function propertyAttribute(ReflectionProperty $property, string $attributeClass): object|null
    {
        $attributes = $property->getAttributes($attributeClass);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }

    /**
     * @template T of object
     * @param ReflectionClass<object> $class
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    private function classAttribute(ReflectionClass $class, string $attributeClass): object|null
    {
        $attributes = $class->getAttributes($attributeClass);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }

    private function declaredType(ReflectionProperty $property, MemoryPackField|null $attribute, string|null $className): string
    {
        if ($attribute?->class !== null || ($attribute?->type === null && $className !== null)) {
            return Type::OBJECT;
        }

        return $attribute?->type ?? $this->inferType($property);
    }

    private function inferType(ReflectionProperty $property): string
    {
        $type = $property->getType();
        if (!$type instanceof ReflectionNamedType) {
            throw new \InvalidArgumentException("Property {$property->getName()} needs a MemoryPackField type.");
        }

        $name = $type->getName();

        return match ($name) {
            'bool' => Type::BOOL,
            'int' => Type::INT32,
            'float' => Type::FLOAT64,
            'string' => Type::STRING,
            'array' => Type::LIST,
            default => class_exists($name) ? Type::OBJECT : throw new \InvalidArgumentException("Unsupported property type {$name}."),
        };
    }

    private function allowsNull(ReflectionProperty $property): bool
    {
        return $property->getType()?->allowsNull() ?? true;
    }

    private function elementDefinition(ReflectionProperty $property, MemoryPackField|null $attribute): FieldDefinition|null
    {
        $type = $attribute?->type ?? $this->safeInferType($property);
        if ($type !== Type::LIST && $type !== Type::DICT) {
            return null;
        }

        return $this->nestedDefinition(
            $property,
            'element',
            $attribute?->elementType,
            $attribute?->elementClass,
        );
    }

    private function keyDefinition(ReflectionProperty $property, MemoryPackField|null $attribute): FieldDefinition|null
    {
        $type = $attribute?->type ?? $this->safeInferType($property);
        if ($type !== Type::DICT) {
            return null;
        }

        return $this->nestedDefinition(
            $property,
            'key',
            $attribute?->keyType,
            $attribute?->keyClass,
        );
    }

    private function nestedDefinition(
        ReflectionProperty $property,
        string $kind,
        string|null $type,
        string|null $className,
    ): FieldDefinition {
        $resolvedType = $className !== null ? Type::OBJECT : $type;
        if ($resolvedType === null) {
            throw new \InvalidArgumentException(
                ucfirst($kind) . " property {$property->getName()} needs MemoryPackField {$kind}Type or {$kind}Class.",
            );
        }

        return new FieldDefinition(
            $property->getName() . ucfirst($kind),
            $resolvedType,
            false,
            null,
            null,
            null,
            null,
            $className,
            $this->isValueType($className),
        );
    }

    private function safeInferType(ReflectionProperty $property): string|null
    {
        try {
            return $this->inferType($property);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function objectClass(ReflectionProperty $property): string|null
    {
        $type = $property->getType();

        return $type instanceof ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null;
    }

    private function isValueType(string|null $className): bool
    {
        if ($className === null || !class_exists($className)) {
            return false;
        }

        $attribute = $this->classAttribute(new ReflectionClass($className), MemoryPackable::class);

        return $attribute?->valueType ?? false;
    }
}
