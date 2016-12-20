<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Phlib\Beanstalk\Factory;

abstract class AbstractCommand extends Command
{
    /**
     * @var \Phlib\Beanstalk\ConnectionInterface
     */
    protected $beanstalk;

    /**
     * @return \Phlib\Beanstalk\ConnectionInterface
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
