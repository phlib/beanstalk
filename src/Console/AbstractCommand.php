<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractCommand
 * @package Phlib\Beanstalk\Console
 */
abstract class AbstractCommand extends Command
{
    private Factory $factory;

    private ConnectionInterface $beanstalk;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;

        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $config = [];
        // Helper will not be defined in unit tests
        if ($this->getHelperSet()->has('configuration')) {
            $config = $this->getHelper('configuration')->fetch();
        }
        $this->beanstalk = $this->factory->createFromArrayBC($config);

        parent::initialize($input, $output);
    }

    final protected function getBeanstalk(): ConnectionInterface
    {
        return $this->beanstalk;
    }
}
