<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerTubesCommand extends Command
{
    protected function configure()
    {
        $this->setName('server:tubes')
            ->setDescription('List all tubes known to the server(s).')
            ->addOption('buried', 'b', InputArgument::OPTIONAL, 'Only list tubes which have buried jobs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
