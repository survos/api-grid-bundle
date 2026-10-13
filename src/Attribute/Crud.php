<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Crud
{
    public function __construct(
        public ?string $prefix,
        public ?array $methods,
    ) {
    }
}
