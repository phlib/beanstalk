<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\StatsService;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStatsCommand extends AbstractStatsCommand
{
    protected function configure(): void
    {
        $this->setName('server:stats')
            ->setDescription('Get a list of details about the beanstalk server(s).')
            ->addOption('stat', 's', InputOption::VALUE_REQUIRED, 'Output a specific statistic.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stat = $input->getOption('stat');
        $service = $this->getStatsService();

        $this->outputDetectedConfig($output);
        if (empty($stat)) {
            $this->outputInfo($service, $output);
            $this->outputStats($service, $output);
        } else {
            $this->outputStat($service, $stat, $output);
        }

        return 0;
    }

    protected function outputDetectedConfig(OutputInterface $output): void
    {
        if (!$output->isVerbose()) {
            return;
        }
        $configPath = $this->getHelper('configuration')->getConfigPath();
        if ($configPath === '[default]') {
            $configPath = '[default fallback localhost]';
        }

        $output->writeln('Configuration: ' . $configPath);
    }

    protected function outputInfo(StatsService $stats, OutputInterface $output): void
    {
        $info = $stats->getServerInfo();

        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');
        $block = $formatter->formatBlock(
            [
                "Host: {$info['hostname']} (pid {$info['pid']})",
                "Beanstalk Version: {$info['version']}",
                "Resources: uptime/{$info['uptime']}, connections/{$info['total-connections']}",
                "Jobs: total/{$info['total-jobs']}, timeouts/{$info['job-timeouts']}",
            ],
            'info',
            true
        );

        $output->writeln('<info>Server Information</info>');
        $output->writeln($block);
    }

    protected function outputStats(StatsService $stats, OutputInterface $output): void
    {
        $binlog = $stats->getServerStats(StatsService::SERVER_BINLOG);
        $command = $stats->getServerStats(StatsService::SERVER_COMMAND);
        $current = $stats->getServerStats(StatsService::SERVER_CURRENT);

        $table = new Table($output);
        $table->setStyle('borderless');
        $table->setHeaders(['Current', 'Stat', 'Command', 'Stat', 'Binlog', 'Stat']);

        $binlogKeys = array_keys($binlog);
        $binlogValues = array_values($binlog);
        $commandKeys = array_keys($command);
        $commandValues = array_values($command);
        $currentKeys = array_keys($current);
        $currentValues = array_values($current);

        $maxRows = max(count($binlog), count($command), count($current));
        for ($i = 0; $i < $maxRows; $i++) {
            $row = [
                $currentKeys[$i] ?? '',
                $currentValues[$i] ?? '',
                $commandKeys[$i] ?? '',
                $commandValues[$i] ?? '',
                $binlogKeys[$i] ?? '',
                $binlogValues[$i] ?? '',
            ];
            $table->addRow($row);
        }

        $output->writeln('<info>Server Statistics</info>');
        $table->render();
    }

    protected function outputStat(StatsService $service, string $stat, OutputInterface $output): void
    {
        $stats = $service->getServerStats(StatsService::SERVER_ALL) + $service->getServerInfo();
        if (!isset($stats[$stat])) {
            throw new InvalidArgumentException("Specified statistic '{$stat}' is not valid.");
        }

        $output->writeln($stats[$stat]);
    }
}
