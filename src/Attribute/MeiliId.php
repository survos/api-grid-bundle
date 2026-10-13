<?php

declare(strict_types=1);

namespace Survos\ApiGridBundle\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
class MeiliId
{
    public function __construct(
    ) {
    }
}
