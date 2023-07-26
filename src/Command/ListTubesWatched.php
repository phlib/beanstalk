<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

/**
 * @package Phlib\Beanstalk
 */
class ListTubesWatched implements CommandInterface
{
    use StatsTrait;

    private function getCommand(): string
    {
        return 'list-tubes-watched';
    }
}
