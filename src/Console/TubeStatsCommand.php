<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TubeStatsCommand extends Command
{
    protected function configure()
    {
        $this->setName('tube:stats')
            ->setDescription('Get statistics for a specific tube.')
            ->addArgument('tube', InputArgument::REQUIRED, 'The tube name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $text = 'Hello';
        if ($name) {
            $text .= ' ' . $name;
        }
        $output->writeln($text);
    }
}
