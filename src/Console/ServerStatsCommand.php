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
//        $headers = [
//            'name'                  => 'Tube',
//            'current-jobs-urgent'   => 'JobsUrgent',
//            'current-jobs-ready'    => 'JobsReady',
//            'current-jobs-reserved' => 'JobsReserved',
//            'current-jobs-delayed'  => 'JobsDelayed',
//            'current-jobs-buried'   => 'JobsBuried',
//            'total-jobs'            => 'Jobs',
//            'current-using'         => 'Using',
//            'current-watching'      => 'Watching',
//            'current-waiting'       => 'Waiting',
//            'cmd-delete'            => 'Delete',
//            'cmd-pause-tube'        => 'PauseTube',
//            'pause'                 => 'Pause',
//            'pause-time-left'       => 'PauseTimeLeft'
//        ];
//
//        if ($input->getOption('tube')) {
//            $tube = $input->getOption('tube');
//            $this->displayForTube($output, $headers, $tube);
//        } elseif ($input->getOption('job')) {
//            $job = $input->getOption('job');
//            $this->displayForJob($output, $job);
//        } else {
//            $this->displayAll($output, $headers);
//        }

        $service = new StatsService($this->getBeanstalk());

        $info = $service->getServerInfo();
        $this->outputServerInfo($info, $output);

        $binlog  = $service->getServerStats(StatsService::SERVER_BINLOG);
        $command = $service->getServerStats(StatsService::SERVER_COMMAND);
        $current = $service->getServerStats(StatsService::SERVER_CURRENT);
        $this->outputServerStats($binlog, $command, $current, $output);

        $tubes = $service->getAllTubeStats();
        $this->outputAllTubeStats($tubes, $output);
    }

    protected function outputServerInfo(array $info, OutputInterface $output)
    {
        $output->writeln('Server Info:');
//        $keys = ['pid', 'hostname', 'id', 'version', 'max-job-size', 'uptime', 'total-jobs', 'job-timeouts', 'max-job-size', 'total-connections', 'rusage-utime', 'rusage-stime'];
        $output->writeln([
            "Host: {$info['hostname']} (pid {$info['pid']})",
            "Beanstalk Version: {$info['version']}",
            "Resources: uptime/{$info['uptime']}, connections/{$info['total-connections']}",
            "Jobs: total/{$info['total-jobs']}, timeouts/{$info['job-timeouts']}",
        ]);
    }

    protected function outputServerStats(array $binlog, array $command, array $current, OutputInterface $output)
    {
        $output->writeln('<info>Server Stats:</info>');
        $table = $this->getTable($output);
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

//        $row = [];
//        $mapping = $this->getServerHeaderMapping();
//        foreach ($stats as $index => $value) {
//            $row[] = $mapping[$index];
//            $row[] = $value;
//            if (count($row) == 8) {
//                $table->addRow($row);
//                $row = [];
//            }
//        }
//        foreach ($this->getServerHeaderMapping() as $index => $header) {
//            $row[] = $header;
//            $row[] = (isset($stats[$index])) ?: 0;
//            if (count($row) == 8) {
//                $table->addRow($row);
//                $row = [];
//            }
//        }
//        if (count($row)) {
//            $row = array_pad($row, 8, '');
//            $table->addRow($row);
//        }
        $table->render();
    }

    protected function outputAllTubeStats(array $stats, OutputInterface $output)
    {
//        var_dump($stats); die;
        $output->writeln('<info>Tube Stats:</info>');
        $table = $this->getTable($output);
        $table->setHeaders($this->getTubeHeaders());
        foreach ($stats as $tubeStats) {
            $table->addRow(array_slice($tubeStats, 0, -3));
        }
        $table->render();
    }

    protected function getServerHeaderMapping()
    {
        return [
            'current-jobs-urgent' => 'CurrentJobsUrgent',
            'current-jobs-ready' => 'CurrentJobsReady',
            'current-jobs-reserved' => 'CurrentJobsReserved',
            'current-jobs-delayed' => 'CurrentJobsDelayed',
            'current-jobs-buried' => 'CurrentJobsBuried',
            'current-tubes' => 'CurrentTubes',
            'current-connections' => 'CurrentConnections',
            'current-producers' => 'CurrentProducers',
            'current-workers' => 'CurrentWorkers',
            'current-waiting' => 'CurrentWaiting',
            'cmd-put' => 'CmdPut',
            'cmd-peek' => 'CmdPeel',
            'cmd-peek-ready' => 'CmdPeekReady',
            'cmd-peek-delayed' => 'CmdPeekDelayed',
            'cmd-peek-buried' => 'CmdPeekBuried',
            'cmd-reserve' => 'CmdReserve',
            'cmd-reserve-with-timeout' => 'CmdReserveWithTimeout',
            'cmd-delete' => 'CmdDelete',
            'cmd-release' => 'CmdRelease',
            'cmd-use' => 'CmdUse',
            'cmd-watch' => 'CmdWatch',
            'cmd-ignore' => 'CmdIgnore',
            'cmd-bury' => 'CmdBury',
            'cmd-kick' => 'CmdKick',
            'cmd-touch' => 'CmdTouch',
            'cmd-stats' => 'CmdStats',
            'cmd-stats-job' => 'CmdStatsJob',
            'cmd-stats-tube' => 'CmdStatsTube',
            'cmd-list-tubes' => 'CmdListTubes',
            'cmd-list-tube-used' => 'CmdListTubeUsed',
            'cmd-list-tubes-watched' => 'CmdListTubesWatched',
            'cmd-pause-tube' => 'CmdPauseJob',
            'rusage-utime' => 'RusageUtime',
            'rusage-stime' => 'RusageStime',
            'binlog-oldest-index' => 'BinlogOldestIndex',
            'binlog-current-index' => 'BinlogCurrentIndex',
            'binlog-records-migrated' => 'BinlogRecordsMigrated',
            'binlog-records-written' => 'BinlogRecordsWritten',
            'binlog-max-size' => 'BinlogMaxSize',
            'total-jobs' => 'TotalJobs',
            'job-timeouts' => 'JobTimeouts',
            'total-connections' => 'TotalConnections',
        ];
    }

    protected function getTubeHeaders()
    {
        return [
            'name' => 'Tube',
            'current-jobs-urgent' => 'JobsUrgent',
            'current-jobs-ready' => 'JobsReady',
            'current-jobs-reserved' => 'JobsReserved',
            'current-jobs-delayed' => 'JobsDelayed',
            'current-jobs-buried' => 'JobsBuried',
            'total-jobs' => 'TotalJobs',
            'current-using' => 'Using',
            'current-watching' => 'Watching',
            'current-waiting' => 'Waiting',
            'cmd-delete' => 'Delete',
//            'cmd-pause-tube' => 'PauseTube',
//            'pause' => 'Pause',
//            'pause-time-left' => 'PauseTimeLeft',
        ];
    }

    protected function displayAll($output, $headers)
    {
        $beanstalk = $this->getBeanstalk();

        $this->displayAllTopLevel($output, $beanstalk);
        $this->displayAllTubes($output, $beanstalk, $headers);
    }

    protected function displayAllTopLevel(OutputInterface $output, BeanstalkInterface $beanstalk)
    {
        //        $formatter = $this->getHelper('formatter');
        //        $formattedBlock = $formatter->formatBlock(['Some Beanstalk Title', 'Some version and pid information'], 'info');
        //        $output->writeln($formattedBlock);

        $output->writeln('<info>Global</info>');
        $table = $this->getTable($output);
        $table->setHeaders(['Name', 'Stat', 'Name', 'Stat', 'Name', 'Stat', 'Name', 'Stat']);
        $stats = $beanstalk->stats();
        $headers = [
            'current-jobs-urgent' => 'CurrentJobsUrgent',
            'current-jobs-ready' => 'CurrentJobsReady',
            'current-jobs-reserved' => 'CurrentJobsReserved',
            'current-jobs-delayed' => 'CurrentJobsDelayed',
            'current-jobs-buried' => 'CurrentJobsBuried',
            'cmd-put' => 'CmdPut',
            'cmd-peek' => 'CmdPeel',
            'cmd-peek-ready' => 'CmdPeekReady',
            'cmd-peek-delayed' => 'CmdPeekDelayed',
            'cmd-peek-buried' => 'CmdPeekBuried',
            'cmd-reserve' => 'CmdReserve',
            'cmd-reserve-with-timeout' => 'CmdReserveWithTimeout',
            'cmd-delete' => 'CmdDelete',
            'cmd-release' => 'CmdRelease',
            'cmd-use' => 'CmdUse',
            'cmd-watch' => 'CmdWatch',
            'cmd-ignore' => 'CmdIgnore',
            'cmd-bury' => 'CmdBury',
            'cmd-kick' => 'CmdKick',
            'cmd-touch' => 'CmdTouch',
            'cmd-stats' => 'CmdStats',
            'cmd-stats-job' => 'CmdStatsJob',
            'cmd-stats-tube' => 'CmdStatsTube',
            'cmd-list-tubes' => 'CmdListTubes',
            'cmd-list-tube-used' => 'CmdListTubeUsed',
            'cmd-list-tubes-watched' => 'CmdListTubesWatched',
            'cmd-pause-tube' => 'CmdPauseJob',
            'job-timeouts' => 'JobTimeouts',
            'total-jobs' => 'TotalJobs',
            'max-job-size' => 'MaxJobSize',
            'current-tubes' => 'CurrentTubes',
            'current-connections' => 'CurrentConnections',
            'current-producers' => 'CurrentProducers',
            'current-workers' => 'CurrentWorkers',
            'current-waiting' => 'CurrentWaiting',
            'total-connections' => 'TotalConnections',
            'pid' => 'Pid',
            'version' => 'Version',
            'rusage-utime' => 'RusageUtime',
            'rusage-stime' => 'RusageStime',
            'uptime' => 'Uptime',
            'binlog-oldest-index' => 'BinlogOldestIndex',
            'binlog-current-index' => 'BinlogCurrentIndex',
            'binlog-records-migrated' => 'BinlogRecordsMigrated',
            'binlog-records-written' => 'BinlogRecordsWritten',
            'binlog-max-size' => 'BinlogMaxSize',
            'id' => 'Id',
            'hostname' => 'Hostname'
        ];

        $row = [];
        foreach ($headers as $index => $header) {
            $row[] = $header;
            $row[] = $stats[$index];
            if (count($row) == 8) {
                $table->addRow($row);
                $row = [];
            }
        }
        if (count($row)) {
            $row = array_pad($row, 8, '');
            $table->addRow($row);
        }
        $table->render();
    }

    protected function displayAllTubes(OutputInterface $output, BeanstalkInterface $beanstalk, $headers)
    {
        $table = $this->getTable($output);
        $table->setHeaders(array_values($headers));
        $output->writeln('<info>Tubes</info>');
        $tubes = $beanstalk->listTubes();
        foreach ($tubes as $tube) {
            $stats = $beanstalk->statsTube($tube);
            $table->addRow($stats);
        }
        $table->render();
    }

    protected function displayForTube(OutputInterface $output, $headers, $tube)
    {
        $table = $this->getTable($output);
        $table->setHeaders(['Name', 'Stat']);

        $beanstalk = $this->getBeanstalk();
        $stats = $beanstalk->statsTube($tube);

        unset($headers[0]);
        foreach ($headers as $index => $header) {
            $table->addRow([$header, $stats[$index]]);
        }
        $table->render();
    }

    protected function displayForJob($output, $jobId)
    {
        $table = $this->getTable($output);
        $headers = [
            'id' => 'JobId',
            'tube' => 'Tube',
            'state' => 'State',
            'pri' => 'Priority',
            'age' => 'Age',
            'delay' => 'Delay',
            'ttr' => 'TTR',
            'time-left' => 'TimeLeft',
            'file' => 'File',
            'reserves' => 'Reserves',
            'timeouts' => 'Timeouts',
            'releases' => 'Releases',
            'buries' => 'Buries',
            'kicks' => 'Kicks'
        ];

        $beanstalk = $this->getBeanstalk();
        $stats = $beanstalk->statsJob($jobId);
        foreach ($headers as $index => $header) {
            $table->addRow([$header, $stats[$index]]);
        }
        $table->render();
    }

    protected function getTable($output)
    {
        $table = new Table($output);
        $table->setStyle('borderless');
        return $table;
    }
}
