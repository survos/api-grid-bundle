<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Controller;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Survos\FieldBundle\Compiler\EntityMetaPass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

class AdminBrowseController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly RouterInterface $router,
    ) {
    }

    // Explicit priority: folio-bundle's FolioController registers unconstrained wildcard routes
    // (`/{folioCode}/{coreCode}`, `/{folioCode}/{coreCode}/{dtoType}/{localId}`, etc. — no regex
    // requirement on coreCode/dtoType/localId), so ANY 2-7 segment path structurally matches one
    // of them. Symfony's router picks the first structurally-matching route by priority then
    // declaration order — it does NOT prefer the more literal/specific pattern — so without this,
    // whichever bundle's routes happen to compile first wins, and /admin/browse/{code} silently
    // resolves as a folio route instead (confirmed via `bin/console router:match`). A route prefix
    // does NOT fix this: it only shifts which folio wildcard route the path collides with.
    #[Route('/admin/browse/{code}', name: 'survos_admin_browse', defaults: ['code' => null], methods: ['GET'], priority: 100)]
    public function browse(?string $code = null): Response
    {
        if (!$code) {
            return $this->render('@SurvosApiGrid/admin/browse.html.twig', [
                'class' => null,
                'code' => null,
                'label' => 'Browse',
                'entities' => $this->entityChoices(),
                'showRoute' => null,
            ]);
        }

        $metadata = $this->metadataForCode($code);
        $class = $metadata->getName();

        return $this->render('@SurvosApiGrid/admin/browse.html.twig', [
            'class' => $class,
            'code' => $code,
            'label' => $metadata->getReflectionClass()->getShortName(),
            'entities' => [],
            'showRoute' => $this->routeExists($code . '_show') ? $code . '_show' : null,
        ]);
    }

    /**
     * @return array<int, array{code: string, class: class-string, label: string, showRoute: ?string}>
     */
    private function entityChoices(): array
    {
        $choices = [];

        foreach ($this->allMetadata() as $metadata) {
            $code = EntityMetaPass::entityCode($metadata->getName());
            $choices[] = [
                'code' => $code,
                'class' => $metadata->getName(),
                'label' => $metadata->getReflectionClass()->getShortName(),
                'showRoute' => $this->routeExists($code . '_show') ? $code . '_show' : null,
            ];
        }

        usort($choices, static fn (array $a, array $b): int => $a['code'] <=> $b['code']);

        return $choices;
    }

    /**
     * @return iterable<ClassMetadata<object>>
     */
    private function allMetadata(): iterable
    {
        foreach ($this->managerRegistry->getManagers() as $manager) {
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
                yield $metadata;
            }
        }
    }

    /**
     * @return ClassMetadata<object>
     */
    private function metadataForCode(string $code): ClassMetadata
    {
        foreach ($this->allMetadata() as $metadata) {
            if (EntityMetaPass::entityCode($metadata->getName()) === $code) {
                return $metadata;
            }
        }

        throw new NotFoundHttpException(sprintf('No Doctrine entity found for browser code "%s".', $code));
    }

    private function routeExists(string $route): bool
    {
        return $this->router->getRouteCollection()->get($route) !== null;
    }
}
