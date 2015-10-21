<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TubePeekCommand extends Command
{
    protected function configure()
    {
        $this->setName('tube:peek')
            ->setDescription('Look at a job in the job based on status.')
            ->addArgument('tube', InputArgument::REQUIRED, 'The tube name.')
            ->addOption('status', 's', InputOption::VALUE_OPTIONAL, 'The tube status. Value can be ready, delayed or buried. Defaults to buried.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
