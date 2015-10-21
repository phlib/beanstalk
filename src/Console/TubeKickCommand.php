<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TubeKickCommand extends Command
{
    protected function configure()
    {
        $this->setName('tube:kick')
            ->setDescription('Kick a number of delayed or buried jobs in the tube.')
            ->addArgument('tube', InputArgument::REQUIRED, 'The tube name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
