<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Facets
{
    public function __construct(
        public array $groups = [],
        public array $properties = [],
        //        public array $methods[]
    ) {
        if (empty($this->groups) && empty($this->properties)) {
            throw new \Exception('Define either groups or properties in Facets()');
        }
    }
}
