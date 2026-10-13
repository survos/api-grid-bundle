<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\capture;
use function Castor\io;

#[AsTask(description: 'Welcome to Castor!')]
function hello(): void
{
    $currentUser = capture('whoami');

    io()->title(sprintf('Hello %s!', $currentUser));
}
