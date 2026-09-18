<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Tests;

use PHPUnit\Framework\TestCase;
use Survos\ApiGridBundle\State\MeiliSearchStateProvider;
use Survos\ApiGridBundle\SurvosApiGridBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

require_once __DIR__.'/../src/SurvosApiGridBundle.php';

final class ProviderRegistrationTest extends TestCase
{
    public function testExplicitProviderIsDiscoverableWithDefaultConfiguration(): void
    {
        $builder = new ContainerBuilder();
        $instanceof = [];
        $configurator = new ContainerConfigurator($builder, new PhpFileLoader($builder, new FileLocator()), $instanceof, __DIR__, __FILE__);
        (new SurvosApiGridBundle())->loadExtension([
            'meiliHost' => 'http://localhost:7700',
            'meiliKey' => null,
            'passLocale' => false,
            'stimulus_controller' => 'survos--api-grid-bundle--api-grid',
            'meili_provider' => false,
        ], $configurator, $builder);
        self::assertArrayHasKey(MeiliSearchStateProvider::class, $builder->findTaggedServiceIds('api_platform.state_provider'));
    }
}
