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
            $config = $this->getHelper('configuration')->fetch();
            if ($config === false) {
                $this->beanstalk = Factory::create('localhost');
            } else {
                $this->beanstalk = Factory::createFromArray($config);
            }
        }

        return $this->beanstalk;
    }
}
