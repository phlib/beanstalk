<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobStatsCommand extends Command
{
    use DefaultConfigureTrait;

    protected function configure()
    {
        $this->setName('job:stats')
            ->setDescription('List statistics related to a specific job.')
            ->addArgument('job-id', InputArgument::REQUIRED, 'The ID of the job.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobId = $input->getArgument('job-id');
        $stats = $this->getBeanstalk()->statsJob($jobId);
        $output->writeln("Found Job '$jobId'.");
        $output->writeln(var_export($stats, true)); // TODO: this needs to be prettier?
    }
}
