<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\Beanstalk
 */
class JobDeleteCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('job:delete')
            ->setDescription('Delete specific job.')
            ->addArgument('job-id', InputArgument::REQUIRED, 'The ID of the job.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobId = $input->getArgument('job-id');
        $this->getBeanstalk()->delete($jobId);
        $output->writeln("Job '{$jobId}' successfully deleted.");

        return 0;
    }
}
