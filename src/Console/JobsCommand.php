<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobsCommand extends Command
{
    protected function configure()
    {
        $this->setName('jobs')
            ->setDescription('CLI for interacting with the Beanstalk server.')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Who do you want to greet?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $text = 'Hello';
        if ($name) {
            $text .= ' ' . $name;
        }
        $output->writeLn($text);
    }
}
