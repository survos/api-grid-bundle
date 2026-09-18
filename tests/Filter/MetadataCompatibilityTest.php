<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Tests\Filter;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;

require_once __DIR__.'/../../src/Filter/MeiliSearch/FilterInterface.php';
require_once __DIR__.'/../../src/Filter/MeiliSearch/UtilTrait.php';
require_once __DIR__.'/../../src/Filter/MeiliSearch/AbstractSearchFilter.php';
if (is_file(__DIR__.'/../../../meili-bundle/src/Filter/MeiliSearch/UtilTrait.php')) {
    require_once __DIR__.'/../../../meili-bundle/src/Filter/MeiliSearch/UtilTrait.php';
    require_once __DIR__.'/../../../meili-bundle/src/Filter/MeiliSearch/AbstractSearchFilter.php';
}

final class MetadataCompatibilityTest extends TestCase
{
    public static function filters(): iterable
    {
        yield 'api-grid' => [GridMetadataProbe::class];
        if (class_exists(\Survos\MeiliBundle\Filter\MeiliSearch\AbstractSearchFilter::class)) {
            yield 'meili' => [MeiliMetadataProbe::class];
        }
    }

    #[DataProvider('filters')]
    public function testScalarNullableAssociationAndNestedCollection(string $filterClass): void
    {
        $metadata = $this->createStub(PropertyMetadataFactoryInterface::class);
        $metadata->method('create')->willReturnCallback(static fn (string $class, string $property): ApiProperty =>
            new ApiProperty(nativeType: match ($property) {
                'title' => Type::nullable(Type::string()),
                'author' => Type::nullable(Type::object(Author::class)),
                'books' => Type::list(Type::object(Book::class)),
                'unknown' => null,
            })
        );
        $resolver = $this->createStub(ResourceClassResolverInterface::class);
        $resolver->method('isResourceClass')->willReturnCallback(static fn (string $class): bool => in_array($class, [Author::class, Book::class], true));
        $filter = new $filterClass($this->createStub(PropertyNameCollectionFactoryInterface::class), $metadata, $resolver, properties: array_fill_keys(['title', 'author', 'author.title', 'books.title', 'author.books.title', 'title.invalid', 'unknown'], null));

        self::assertEquals(Type::nullable(Type::string()), $filter->metadata(Book::class, 'title')[0]);
        self::assertTrue($filter->metadata(Book::class, 'author')[1]);
        self::assertSame(Author::class, $filter->metadata(Book::class, 'author.title')[2]);
        self::assertSame(Book::class, $filter->metadata(Book::class, 'books.title')[2]);
        self::assertSame([null, null, null, null], $filter->metadata(Book::class, 'title.invalid'));
        self::assertSame([null, null, null, null], $filter->metadata(Book::class, 'unknown'));
        self::assertSame([null, null, null, null], $filter->metadata(Book::class, 'disabled'));
        self::assertNull($filter->nested(Book::class, 'author.title'));
        self::assertSame('books', $filter->nested(Book::class, 'books.title'));
        self::assertSame('author.books', $filter->nested(Book::class, 'author.books.title'));
    }
}

trait MetadataProbe
{
    public function metadata(string $class, string $property): array { return $this->getMetadata($class, $property); }
    public function nested(string $class, string $property): ?string { return $this->getNestedFieldPath($class, $property); }
    public function getDescription(string $resourceClass): array { return []; }
    public function apply(array $clauseBody, string $resourceClass, ?Operation $operation = null, array $context = []): array { return $clauseBody; }
}

final class GridMetadataProbe extends \Survos\ApiGridBundle\Filter\MeiliSearch\AbstractSearchFilter { use MetadataProbe; }
if (class_exists(\Survos\MeiliBundle\Filter\MeiliSearch\AbstractSearchFilter::class)) {
    final class MeiliMetadataProbe extends \Survos\MeiliBundle\Filter\MeiliSearch\AbstractSearchFilter { use MetadataProbe; }
}
final class Author {}
final class Book {}
