<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

/**
 * @package Phlib\Beanstalk
 */
class Stats implements CommandInterface
{
    use StatsTrait;

    private function getCommand(): string
    {
        return 'stats';
    }
}
