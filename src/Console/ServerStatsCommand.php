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
            ->setDescription('Get a list of details about the beanstalk server(s).');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = new StatsService($this->getBeanstalk());
        $this->outputServerInfo($service, $output);
        $this->outputServerStats($service, $output);
    }

    protected function outputServerInfo(StatsService $stats, OutputInterface $output)
    {
        $info = $stats->getServerInfo();

        /* @var $formatter \Symfony\Component\Console\Helper\FormatterHelper */
        $formatter = $this->getHelper('formatter');
        $block = $formatter->formatBlock(
            [
                "Host: {$info['hostname']} (pid {$info['pid']})",
                "Beanstalk Version: {$info['version']}",
                "Resources: uptime/{$info['uptime']}, connections/{$info['total-connections']}",
                "Jobs: total/{$info['total-jobs']}, timeouts/{$info['job-timeouts']}"
            ],
            'info',
            true
        );

        $output->writeln('<info>Server Information</info>');
        $output->writeln($block);
    }

    protected function outputServerStats(StatsService $stats, OutputInterface $output)
    {
        $binlog  = $stats->getServerStats(StatsService::SERVER_BINLOG);
        $command = $stats->getServerStats(StatsService::SERVER_COMMAND);
        $current = $stats->getServerStats(StatsService::SERVER_CURRENT);

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

        $output->writeln('<info>Server Statistics</info>');
        $table->render();
    }
}
