<?php

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\BeanstalkInterface;
use Phlib\Beanstalk\StatsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ServerStatsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('server:stats')
            ->setDescription('Get a list of details about the beanstalk server(s).')
            ->addOption('exclude-tubes', 'e', InputOption::VALUE_NONE, 'Exclude tube stats from the output.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = new StatsService($this->getBeanstalk());
        $this->outputServerInfo($service, $output);
        $this->outputServerStats($service, $output);
        if (!$input->getOption('exclude-tubes')) {
            $this->outputAllTubeStats($service, $output);
        }
    }

    protected function outputServerInfo(StatsService $stats, OutputInterface $output)
    {
        $info = $stats->getServerInfo();

        $output->writeln('Server Info:');
        $output->writeln([
            "Host: {$info['hostname']} (pid {$info['pid']})",
            "Beanstalk Version: {$info['version']}",
            "Resources: uptime/{$info['uptime']}, connections/{$info['total-connections']}",
            "Jobs: total/{$info['total-jobs']}, timeouts/{$info['job-timeouts']}",
        ]);
    }

    protected function outputServerStats(StatsService $stats, OutputInterface $output)
    {
        $binlog  = $stats->getServerStats(StatsService::SERVER_BINLOG);
        $command = $stats->getServerStats(StatsService::SERVER_COMMAND);
        $current = $stats->getServerStats(StatsService::SERVER_CURRENT);

        $output->writeln('<info>Server Stats:</info>');
        $table = new Table($output);
        $table->setStyle('borderless');
        $table->setHeaders(['Current', 'Stat', 'Command', 'Stat', 'Binlog', 'Stat']);

        $binlogKeys    = array_keys($binlog);
        $binlogValues  = array_values($binlog);
        $commandKeys   = array_keys($command);
        $commandValues = array_values($command);
        $currentKeys   = array_keys($current);
        $currentValues = array_values($current);

        $maxRows = max(count($binlog), count($command), count($current));
        for ($i=0; $i < $maxRows; $i++) {
            $row = [
                (isset($currentKeys[$i])) ? $currentKeys[$i] : '',
                (isset($currentValues[$i])) ? $currentValues[$i] : '',
                (isset($commandKeys[$i])) ? $commandKeys[$i] : '',
                (isset($commandValues[$i])) ? $commandValues[$i] : '',
                (isset($binlogKeys[$i])) ? $binlogKeys[$i] : '',
                (isset($binlogValues[$i])) ? $binlogValues[$i] : '',
            ];
            $table->addRow($row);
        }
        $table->render();
    }

    protected function outputAllTubeStats(StatsService $stats, OutputInterface $output)
    {
        $tubes = $stats->getAllTubeStats();

        $output->writeln('<info>Tube Stats:</info>');
        $table = new Table($output);
        $table->setStyle('borderless');
        $table->setHeaders($stats->getTubeHeaderMapping());
        foreach ($tubes as $tubeStats) {
            $table->addRow(array_slice($tubeStats, 0, -3));
        }
        $table->render();
    }
}
