<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Stats\Service;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ServerStatsCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('server:stats')
            ->setDescription('Get a list of details about the beanstalk server(s).')
            ->addOption('stat', 's', InputOption::VALUE_REQUIRED, 'Output a specific statistic.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stat    = $input->getOption('stat');
        $service = new Service($this->getBeanstalk());

        $this->outputDetectedConfig($output);
        if ($stat == '') {
            $this->outputInfo($service, $output);
            $this->outputStats($service, $output);
        } else {
            $this->outputStat($service, $stat, $output);
        }
    }

    protected function outputDetectedConfig(OutputInterface $output): void
    {
        if (!$output->isVerbose()) {
            return;
        }
        $configPath = $this->getHelper('configuration')->getConfigPath();
        if ($configPath == '[default]') {
            $configPath = '[default fallback localhost]';
        }

        $output->writeln('Configuration: ' . $configPath);
    }

    protected function outputInfo(Service $stats, OutputInterface $output): void
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

    /**
     * @param Service $stats
     * @param OutputInterface $output
     */
    protected function outputStats(Service $stats, OutputInterface $output): void
    {
        $binlog  = $stats->getServerStats(Service::SERVER_BINLOG);
        $command = $stats->getServerStats(Service::SERVER_COMMAND);
        $current = $stats->getServerStats(Service::SERVER_CURRENT);

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

    /**
     * @param Service $service
     * @param string $stat
     * @param OutputInterface $output
     * @throws InvalidArgumentException
     */
    protected function outputStat(Service $service, $stat, OutputInterface $output): void
    {
        $stats = $service->getServerStats(Service::SERVER_ALL) + $service->getServerInfo();
        if (!isset($stats[$stat])) {
            throw new InvalidArgumentException("Specified statistic '$stat' is not valid.");
        }

        $output->writeln($stats[$stat]);
    }
}
