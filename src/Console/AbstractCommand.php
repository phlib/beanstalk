<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\ConnectionInterface;
use Symfony\Component\Console\Command\Command;
use Phlib\Beanstalk\Factory;

abstract class AbstractCommand extends Command
{
    /**
     * @var ConnectionInterface
     */
    protected $beanstalk;

    /**
     * @return ConnectionInterface
     */
    public function getBeanstalk(): ConnectionInterface
    {
        if (!$this->beanstalk) {
            $config = $this->getHelper('configuration')->fetch();
            $this->beanstalk = Factory::createFromArray($config);
        }

        return $this->beanstalk;
    }
}
