<?php

declare(strict_types=1);

namespace MemoryPack\Mapping;

use MemoryPack\Mapping\Attributes\MemoryPackable;
use MemoryPack\Mapping\Attributes\MemoryPackField;
use MemoryPack\Mapping\Attributes\MemoryPackFormatter;
use MemoryPack\Mapping\Attributes\MemoryPackUnion;
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
        if (is_subclass_of($className, MemoryPackSchemaInterface::class)) {
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
            if ($fieldAttribute === null) {
                continue;
            }

            $fields[] = [
                'order' => $fieldAttribute?->order ?? $index,
                'field' => $this->createField($property, $fieldAttribute),
            ];
            $index++;
        }

        usort($fields, static fn (array $left, array $right): int => $left['order'] <=> $right['order']);

        return $this->cache[$className] = new Schema(
            array_column($fields, 'field'),
            $className,
            $memoryPackable?->valueType ?? false,
            $this->unionTags($class),
        );
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
            $attribute?->nullable ?? false,
            $this->elementDefinition($property, $attribute),
            $this->keyDefinition($property, $attribute),
            $attribute?->formatter ?? $formatterAttribute?->formatterClass,
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

    /**
     * @param ReflectionClass<object> $class
     * @return array<int, class-string>
     */
    private function unionTags(ReflectionClass $class): array
    {
        $tags = [];
        $classes = [];

        foreach ($class->getAttributes(MemoryPackUnion::class) as $attribute) {
            $union = $attribute->newInstance();
            if ($union->tag < 0 || $union->tag > 0xffff) {
                throw new \InvalidArgumentException("MemoryPackUnion tag {$union->tag} is out of range.");
            }
            if (isset($tags[$union->tag])) {
                throw new \InvalidArgumentException("Duplicate MemoryPackUnion tag {$union->tag}.");
            }
            if (isset($classes[$union->class])) {
                throw new \InvalidArgumentException("Duplicate MemoryPackUnion class {$union->class}.");
            }
            if (!class_exists($union->class)) {
                throw new \InvalidArgumentException("MemoryPackUnion class {$union->class} does not exist.");
            }
            if (!is_a($union->class, $class->getName(), true)) {
                throw new \InvalidArgumentException("MemoryPackUnion class {$union->class} must extend or implement {$class->getName()}.");
            }

            $tags[$union->tag] = $union->class;
            $classes[$union->class] = true;
        }

        ksort($tags);

        return $tags;
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
            default => class_exists($name) || interface_exists($name) ? Type::OBJECT : throw new \InvalidArgumentException("Unsupported property type {$name}."),
        };
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
            $attribute?->element,
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
            $attribute?->key,
        );
    }

    private function nestedDefinition(
        ReflectionProperty $property,
        string $kind,
        MemoryPackField|null $definition,
    ): FieldDefinition {
        if ($definition !== null) {
            return $this->fieldDefinitionFromAttribute($property->getName() . ucfirst($kind), $definition);
        }

        throw new \InvalidArgumentException(
            ucfirst($kind) . " property {$property->getName()} needs MemoryPackField {$kind}.",
        );
    }

    private function fieldDefinitionFromAttribute(string $name, MemoryPackField $attribute): FieldDefinition
    {
        $type = $attribute->class !== null ? Type::OBJECT : $attribute->type;
        if ($type === null) {
            throw new \InvalidArgumentException(
                "Nested MemoryPackField {$name} needs type or class.",
            );
        }
        $className = $type === Type::OBJECT ? $attribute->class : null;

        return new FieldDefinition(
            $name,
            $type,
            $attribute->nullable ?? false,
            $attribute->element === null ? null : $this->fieldDefinitionFromAttribute($name . 'Element', $attribute->element),
            $attribute->key === null ? null : $this->fieldDefinitionFromAttribute($name . 'Key', $attribute->key),
            $attribute->formatter,
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
