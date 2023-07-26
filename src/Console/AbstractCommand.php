<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\Beanstalk
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

    protected function configure(): void
    {
        $this->addOption(
            'host',
            'H',
            InputOption::VALUE_REQUIRED,
            'Connect to Beanstalk server host (for testing, alternative to reading a config file)',
        );
        $this->addOption(
            'port',
            'P',
            InputOption::VALUE_REQUIRED,
            'Connect to Beanstalk server port (only applies when passing --host)',
            Socket::DEFAULT_PORT,
        );
        parent::configure();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $config = [];
        $host = $input->getOption('host');

        if (isset($host)) {
            $config = [
                'host' => $host,
                'port' => (int)$input->getOption('port'),
            ];
        } elseif ($this->getHelperSet()->has('configuration')) {
            // Helper will not be defined in unit tests
            $config = $this->getHelper('configuration')->fetch();
        }

        $this->beanstalk = $this->factory->createFromArray($config);
    }

    final protected function getBeanstalk(): ConnectionInterface
    {
        return $this->beanstalk;
    }
}
