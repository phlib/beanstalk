<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\Beanstalk
 */
class TubePeekCommand extends AbstractCommand
{
    use DisplayJobTrait;

    protected function configure(): void
    {
        $this->setName('tube:peek')
            ->setDescription('Look at a job in the job based on status.')
            ->addArgument(
                'tube',
                InputArgument::REQUIRED,
                'The tube name',
            )
            ->addArgument(
                'status',
                InputArgument::REQUIRED,
                'The tube status <comment>[buried|delayed|ready]</comment>',
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = $input->getArgument('status');

        // Fail early if status is not supported
        if (!in_array($status, ['ready', 'delayed', 'buried'], true)) {
            throw new InvalidArgumentException("Specified status '{$status}' is not valid.");
        }

        $this->getBeanstalk()
            ->useTube($input->getArgument('tube'));

        // Use switch instead of `->{'peek' . $status}` to allow static analysis
        switch ($status) {
            case 'ready':
                $job = $this->getBeanstalk()->peekReady();
                break;
            case 'delayed':
                $job = $this->getBeanstalk()->peekDelayed();
                break;
            case 'buried':
                $job = $this->getBeanstalk()->peekBuried();
                break;
            default:
                throw new InvalidArgumentException("Specified status '{$status}' is not valid.");
        }

        if ($job === null) {
            $output->writeln("No jobs found in '{$status}' status.");
        } else {
            $this->displayJob($job, $output);
        }

        return 0;
    }
}
