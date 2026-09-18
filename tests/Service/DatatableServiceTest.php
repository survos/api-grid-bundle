<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Tests\Service;

use Doctrine\ORM\Mapping as ORM;
use PHPUnit\Framework\TestCase;
use Survos\ApiGridBundle\Service\DatatableService;
use Survos\FieldBundle\Attribute\Field;
use Survos\FieldBundle\Service\FieldReader;

require_once __DIR__.'/../../src/Model/Column.php';
require_once __DIR__.'/../../src/Service/DatatableService.php';

final class DatatableServiceTest extends TestCase
{
    public function testDiscoveredIdentifiersNormalizeIntoRenderableColumns(): void
    {
        $service = new DatatableService(new FieldReader());
        $settings = $service->getSettingsFromAttributes(GridRecord::class);
        self::assertSame(['id'], $service->getFieldsWithAttribute($settings, 'is_primary'));
        $columns = [...$service->normalizedColumns($settings, array_values($settings), ['id' => 'record-id.html.twig'])];
        self::assertCount(2, $columns);
        self::assertSame('id', $columns[0]->name);
        self::assertTrue($columns[0]->sortable);
        self::assertSame('record-id.html.twig', $columns[0]->twigTemplate);
        self::assertSame('name', $columns[1]->name);
        self::assertTrue($columns[1]->searchable);
    }
}

final class GridRecord
{
    #[ORM\Id, Field(sortable: true)]
    public int $id;

    #[Field(searchable: true)]
    public string $name;
}
