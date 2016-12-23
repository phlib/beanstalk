<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

class Stats implements CommandInterface
{
    use StatsTrait;

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'stats';
    }
}
