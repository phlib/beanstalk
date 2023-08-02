<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\Beanstalk
 */
class TubeKickCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('tube:kick')
            ->setDescription('Kick a number of delayed or buried jobs in the tube.')
            ->addArgument('tube', InputArgument::REQUIRED, 'The tube name.')
            ->addArgument('quantity', InputArgument::REQUIRED, 'The number of jobs to kick.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $quantity = $this->getBeanstalk()
            ->useTube($input->getArgument('tube'))
            ->kick((int)$input->getArgument('quantity'));
        $output->writeln("Successfully kicked {$quantity} jobs.");

        return 0;
    }
}
