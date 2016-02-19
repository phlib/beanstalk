<?php

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TubePeekCommand extends AbstractCommand
{
    use DisplayJobTrait;

    protected function configure()
    {
        $this->setName('tube:peek')
            ->setDescription('Look at a job in the job based on status.')
            ->addArgument('tube', InputArgument::REQUIRED, 'The tube name.')
            ->addOption('status', 's', InputOption::VALUE_OPTIONAL, 'The tube status. Value can be ready, delayed or buried.', 'buried');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $status = $input->getOption('status');
        if (!in_array($status, ['ready', 'delayed', 'buried'], true)) {
            throw new InvalidArgumentException("Specified status '$status' is not valid.");
        }

        $this->getBeanstalk()
            ->useTube($input->getArgument('tube'));
        $method = 'peek' . ucfirst($status);
        $job = call_user_func([$this->getBeanstalk(), $method]);

        if ($job === false) {
            $output->writeln("No jobs found in '$status' status.");
        } else {
            $this->displayJob($job, $output);
        }
    }
}
