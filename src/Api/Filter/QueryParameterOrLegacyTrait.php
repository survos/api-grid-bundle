<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Api\Filter;

use ApiPlatform\Metadata\Parameter;

/**
 * Lets one filter class serve both ways API Platform can declare it: as the `filter:` of a
 * QueryParameter (the value and properties come from the parameter), or through the deprecated
 * #[ApiFilter] attribute (the value is looked up in the query string by name, the properties
 * come from the constructor).
 *
 * The using class needs `$properties` (?array) and `$searchParameterName` (string).
 */
trait QueryParameterOrLegacyTrait
{
    private function requestedValue(array $context): mixed
    {
        $parameter = $context['parameter'] ?? null;

        return $parameter instanceof Parameter
            ? $parameter->getValue()
            : ($context['filters'][$this->searchParameterName] ?? null);
    }

    /** @return list<string> */
    private function declaredProperties(array $context): array
    {
        $parameter = $context['parameter'] ?? null;

        return self::propertyNames($this->properties ?? ($parameter instanceof Parameter ? $parameter->getProperties() : null) ?? []);
    }

    /**
     * Properties may be given as ['title', 'overview'] or, the way #[ApiFilter] passes them on,
     * as ['title' => null, 'overview' => null].
     *
     * @return list<string>
     */
    private static function propertyNames(array $properties): array
    {
        $names = [];
        foreach ($properties as $property => $strategy) {
            $names[] = (string) (\is_int($property) ? $strategy : $property);
        }

        return $names;
    }
}
