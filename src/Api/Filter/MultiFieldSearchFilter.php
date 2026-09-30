<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Api\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * The grid's search box: keeps the rows where every word of the search is found in at least
 * one of the properties. Words are separated by spaces; matching is case-insensitive.
 * All properties must be strings.
 *
 * Declare it as a query parameter:
 *
 *     'search' => new QueryParameter(filter: new MultiFieldSearchFilter(), properties: ['name', 'code'])
 *
 * The older `#[ApiFilter(MultiFieldSearchFilter::class, properties: [...])]` form still works.
 */
final class MultiFieldSearchFilter implements FilterInterface
{
    use QueryParameterOrLegacyTrait;

    public function __construct(
        ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
        private readonly ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
        private readonly string $searchParameterName = 'search',
    ) {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $value = $this->requestedValue($context);
        $properties = $this->declaredProperties($context);
        if (!\is_scalar($value) || !$properties) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        foreach (preg_split('/\s+/', trim((string) $value), -1, \PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $parameterName = $queryNameGenerator->generateParameterName($this->searchParameterName);
            $anyProperty = $queryBuilder->expr()->orX();
            foreach ($properties as $property) {
                $anyProperty->add($queryBuilder->expr()->like('LOWER('.$alias.'.'.$property.')', ':'.$parameterName));
            }
            $queryBuilder
                ->andWhere($anyProperty)
                ->setParameter($parameterName, '%'.mb_strtolower($word).'%');
        }
    }

    public function getDescription(string $resourceClass): array
    {
        if (null === $this->properties) {
            return [];
        }

        return [
            $this->searchParameterName => [
                'property' => implode(', ', self::propertyNames($this->properties)),
                'type' => 'string',
                'required' => false,
                'openapi' => [
                    'description' => 'Selects entities where each search term is found somewhere in at least one of the specified properties',
                ],
            ],
        ];
    }
}
