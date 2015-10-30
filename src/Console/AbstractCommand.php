<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Phlib\Beanstalk\Factory;

/**
 * Class AbstractCommand
 * @package Phlib\Beanstalk\Console
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var \Phlib\Beanstalk\Connection\ConnectionInterface
     */
    protected $beanstalk;

    /**
     * @return \Phlib\Beanstalk\Connection\ConnectionInterface
     */
    public function getBeanstalk()
    {
        if (!$this->beanstalk) {
            $factory = new Factory;
            $config = $this->getHelper('configuration')->fetch();
            if ($config === false) {
                $this->beanstalk = $factory->create('localhost');
            } else {
                $this->beanstalk = $factory->createFromArray($config);
            }
        }

        return $this->beanstalk;
    }
}
