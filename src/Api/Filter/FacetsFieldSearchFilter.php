<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Api\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * The grid's facet (search pane) filter: `facet_filter[]=<field>,<operator>,<value>|<value>`.
 * Each entry keeps the rows whose field is one of the values; an empty value list means
 * "is null". Fields that are not declared, or that are not a plain column
 * or to-one association of the entity, are ignored.
 *
 * Declare it as a query parameter:
 *
 *     'facet_filter' => new QueryParameter(filter: new FacetsFieldSearchFilter(), properties: ['marking', 'host'])
 *
 * The older `#[ApiFilter(FacetsFieldSearchFilter::class, properties: [...])]` form still works.
 */
class FacetsFieldSearchFilter implements FilterInterface
{
    use QueryParameterOrLegacyTrait;

    public function __construct(
        private readonly ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
        private readonly ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
        private readonly string $searchParameterName = 'facet_filter',
    ) {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $value = $this->requestedValue($context);
        if (null === $value || '' === $value) {
            return;
        }

        $allowed = $this->declaredProperties($context);
        $metadata = $queryBuilder->getEntityManager()->getClassMetadata($resourceClass);
        $alias = $queryBuilder->getRootAliases()[0];

        foreach ((array) $value as $facet) {
            $parts = explode(',', (string) $facet, 3);
            if (3 !== \count($parts)) {
                continue;
            }
            [$field, , $values] = $parts;
            if (!\in_array($field, $allowed, true) || !$this->isComparable($metadata, $field)) {
                continue;
            }

            if ('' === $values) {
                $queryBuilder->andWhere($queryBuilder->expr()->isNull($alias.'.'.$field));
                continue;
            }
            $parameterName = $queryNameGenerator->generateParameterName($field);
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in($alias.'.'.$field, ':'.$parameterName))
                ->setParameter($parameterName, explode('|', $values));
        }
    }

    /** A mapped scalar column or a to-one association: something `IN (...)` can be applied to. */
    private function isComparable(ClassMetadata $metadata, string $field): bool
    {
        if ($metadata->hasAssociation($field)) {
            return $metadata->isSingleValuedAssociation($field);
        }

        return $metadata->hasField($field) && !\in_array($metadata->getTypeOfField($field), ['json', 'array', 'simple_array'], true);
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
                'is_collection' => true,
                'required' => false,
                'openapi' => [
                    'description' => 'Facet filter, one entry per field: <field>,<operator>,<value>|<value>',
                ],
            ],
        ];
    }
}
