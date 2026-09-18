<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Filter\MeiliSearch;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;

use function array_shift;
use function explode;
use function implode;
use function method_exists;

/** Field datatype helpers shared by the Meilisearch filters. */
trait UtilTrait
{
    private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory;
    private readonly ?ResourceClassResolverInterface $resourceClassResolver;

    private function getPropertyType(ApiProperty $property): ?Type
    {
        if (method_exists($property, 'getNativeType')) {
            return $property->getNativeType();
        }

        // API Platform 4.0/4.1 predates native TypeInfo metadata.
        $legacy = $property->getBuiltinTypes()[0] ?? null;
        if (null === $legacy) {
            return null;
        }

        return $this->convertLegacyType($legacy);
    }

    private function convertLegacyType(\Symfony\Component\PropertyInfo\Type $legacy): Type
    {
        if ($legacy->isCollection()) {
            $value = $legacy->getCollectionValueTypes()[0] ?? null;
            $type = Type::array(null === $value ? Type::mixed() : $this->convertLegacyType($value));
        } elseif (null !== $class = $legacy->getClassName()) {
            $type = Type::object($class);
        } else {
            $type = Type::builtin($legacy->getBuiltinType());
        }

        return $legacy->isNullable() ? Type::nullable($type) : $type;
    }

    private function getCollectionType(Type $type): ?CollectionType
    {
        foreach ($type->traverse() as $part) {
            if ($part instanceof CollectionType) {
                return $part;
            }
        }

        return null;
    }

    private function getObjectClass(Type $type): ?string
    {
        foreach ($type->traverse() as $part) {
            if ($part instanceof ObjectType) {
                return $part->getClassName();
            }
        }

        return null;
    }

    private function isNestedField(string $resourceClass, string $property): bool
    {
        return null !== $this->getNestedFieldPath($resourceClass, $property);
    }

    private function getNestedFieldPath(string $resourceClass, string $property): ?string
    {
        $properties = explode('.', $property);
        $currentProperty = array_shift($properties);
        if (!$properties) {
            return null;
        }

        try {
            $type = $this->getPropertyType($this->propertyMetadataFactory->create($resourceClass, $currentProperty));
        } catch (PropertyNotFoundException) {
            return null;
        }

        if (null === $type) {
            return null;
        }

        $collection = $this->getCollectionType($type);
        $className = $this->getObjectClass($collection?->getCollectionValueType() ?? $type);
        if (null === $className || !$this->resourceClassResolver?->isResourceClass($className)) {
            return null;
        }

        $nestedPath = $this->getNestedFieldPath($className, implode('.', $properties));
        if (null !== $nestedPath) {
            return "$currentProperty.$nestedPath";
        }

        return null !== $collection ? $currentProperty : null;
    }
}
