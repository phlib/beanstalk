<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

/**
 * Class Stats
 * @package Phlib\Beanstalk\Command
 */
class Stats implements CommandInterface
{
    use StatsTrait;

    public function getCommand(): string
    {
        return 'stats';
    }
}
