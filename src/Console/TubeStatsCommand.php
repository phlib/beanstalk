<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TubeStatsCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('tube:stats')
            ->setDescription('Display stats for the specified tube.')
            ->addArgument('tube', InputArgument::REQUIRED, 'Name of the tube.')
            ->addOption('stat', 's', InputOption::VALUE_REQUIRED, 'Output a specific statistic.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tube = $input->getArgument('tube');
        $stat = $input->getOption('stat');

        $service = $this->getStatsService();
        $stats = $service->getTubeStats($tube);

        if (empty($stats)) {
            $output->writeln("No statistics found for tube '{$tube}'.");
            return 0;
        }

        if (empty($stat)) {
            $this->displayTable($stats, $output);
        } else {
            $this->displayStat($stats, $stat, $output);
        }

        return 0;
    }

    protected function displayTable(array $stats, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setHeaders(['Statistic', 'Total']);
        foreach ($stats as $stat => $total) {
            if ($stat === 'current-jobs-buried' && $total > 0) {
                $stat = "<error>{$stat}</error>";
                $total = "<error>{$total}</error>";
            }
            $table->addRow([$stat, $total]);
        }

        $table->render();
    }

    protected function displayStat(array $stats, string $stat, OutputInterface $output): void
    {
        if (!isset($stats[$stat])) {
            throw new InvalidArgumentException("Specified statistic '{$stat}' is not valid.");
        }

        $output->writeln($stats[$stat]);
    }
}
