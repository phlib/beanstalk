<?php

namespace Phlib\Beanstalk\Command;

/**
 * Class Stats
 * @package Phlib\Beanstalk\Command
 */
class Stats implements CommandInterface
{
    use StatsTrait;
    use ToStringTrait;

    /**
     * @return string
     */
    public function getCommand()
    {
        return 'stats';
    }
}
