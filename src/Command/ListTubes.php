<?php

namespace Phlib\Beanstalk\Command;

/**
 * Class ListTubes
 * @package Phlib\Beanstalk\Command
 */
class ListTubes implements CommandInterface
{
    use StatsTrait;
    use ToStringTrait;

    /**
     * @return string
     */
    public function getCommand()
    {
        return 'list-tubes';
    }
}
