<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class FacetEvent extends Event
{
    public function __construct(
        private array $facets,
        private ?string $sourceLocale = null,
        private ?string $targetLocale = null,
        public array $context = [],
    ) {
    }

    public function getTargetLocale(): string
    {
        return $this->targetLocale;
    }

    public function getFacets(): array
    {
        return $this->facets;
    }

    public function setFacets(array $facets): FacetEvent
    {
        $this->facets = $facets;

        return $this;
    }
}
