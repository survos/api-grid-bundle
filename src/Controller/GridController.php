<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

class GridController extends AbstractController
{
    protected $workflowRegistry;

    protected $helper;

    public function __construct(
        private ?ChartBuilderInterface $chartBuilder = null,
    ) {
        //        $this->helper = $helper;
    }

    #[Route('/columns/{entity}', name: 'survos_grid_columns')]
    public function columns(Request $request, string $entity)
    {
        // inspect the entity and colum?
        dd($entity);

        return $this->render('@SurvosWorkflow/index.html.twig', [
            'configs' => $this->helper->getWorkflowConfiguration(),
            'workflowsGroupedByClass' => $workflowsGroupedByClass,
            'workflowsByCode' => $workflowsGroupedByCode,
        ]);
    }
}
