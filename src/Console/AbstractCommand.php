<?php

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Symfony\Component\Console\Command\Command;
use Phlib\Beanstalk\Factory;

/**
 * Class AbstractCommand
 * @package Phlib\Beanstalk\Console
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var ConnectionInterface
     */
    protected $beanstalk;

    /**
     * @return ConnectionInterface
     */
    public function getBeanstalk()
    {
        if (!$this->beanstalk) {
            $config = $this->getHelper('configuration')->fetch();
            $this->beanstalk = Factory::createFromArray($config);
        }

        return $this->beanstalk;
    }
}
