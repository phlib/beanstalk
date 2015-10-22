<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobDeleteCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('job:delete')
            ->setDescription('Delete specific job.')
            ->addArgument('job-id', InputArgument::REQUIRED, 'The ID of the job.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobId = $input->getArgument('job-id');
        $this->getBeanstalk()->delete($jobId);
        $output->writeln("Job '$jobId' successfully deleted.");
    }
}
