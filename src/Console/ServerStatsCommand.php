<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Console\Service\StatsService;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @package Phlib\Beanstalk
 */
class ServerStatsCommand extends AbstractStatsCommand
{
    protected function configure(): void
    {
        $this->setName('server:stats')
            ->setDescription('Get a list of details about the beanstalk server(s).')
            ->addOption('stat', 's', InputOption::VALUE_REQUIRED, 'Output a specific statistic.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stat = $input->getOption('stat');
        $service = $this->getStatsService();

        $io = new SymfonyStyle($input, $output);

        if ($output->isVerbose()) {
            $this->outputDetectedConfig($io);
        }

        if (empty($stat)) {
            $this->outputInfo($service, $io);
            $this->outputStats($service, $io);
        } else {
            $this->outputStat($service, $stat, $output);
        }

        return 0;
    }

    private function outputDetectedConfig(SymfonyStyle $io): void
    {
        $configPath = $this->getHelper('configuration')->getConfigPath();
        if ($configPath === '[default]') {
            $configPath = '[default fallback localhost]';
        }

        $io->comment('Configuration: ' . $configPath);
    }

    private function outputInfo(StatsService $stats, SymfonyStyle $io): void
    {
        $info = $stats->getServerInfo();

        $io->section('Server Information');
        $io->definitionList(
            ['Host' => sprintf('%s (pid %d)', $info['hostname'], $info['pid'])],
            ['Beanstalk Version' => $info['version']],
            ['Resources' => sprintf('uptime/%d, connections/%d', $info['uptime'], $info['total-connections'])],
            ['Jobs' => sprintf('total/%d, timeouts/%d', $info['total-jobs'], $info['job-timeouts'])],
        );
    }

    private function outputStats(StatsService $stats, SymfonyStyle $io): void
    {
        $binlog = $stats->getServerStats(StatsService::SERVER_BINLOG);
        $command = $stats->getServerStats(StatsService::SERVER_COMMAND);
        $current = $stats->getServerStats(StatsService::SERVER_CURRENT);

        $headers = ['Current', 'Stat', 'Command', 'Stat', 'Binlog', 'Stat'];

        $binlogKeys = array_keys($binlog);
        $binlogValues = array_values($binlog);
        $commandKeys = array_keys($command);
        $commandValues = array_values($command);
        $currentKeys = array_keys($current);
        $currentValues = array_values($current);

        $rows = [];
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
            $rows[] = $row;
        }

        $io->section('Server Statistics');
        $io->table($headers, $rows);
    }

    private function outputStat(StatsService $service, string $stat, OutputInterface $output): void
    {
        $stats = array_merge($service->getServerStats(StatsService::SERVER_ALL), $service->getServerInfo());
        if (!isset($stats[$stat])) {
            throw new InvalidArgumentException("Specified statistic '{$stat}' is not valid.");
        }

        $output->writeln($stats[$stat]);
    }
}
