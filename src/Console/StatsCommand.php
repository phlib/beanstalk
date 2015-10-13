<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class StatsCommand extends Command
{
    protected function configure()
    {
        $this->setName('stats')
            ->setDescription('CLI for interacting with the Beanstalk server.')
            ->addOption(
                'tube',
                't',
                InputOption::VALUE_REQUIRED,
                'If set, the stats will pulled specifically for this tube.'
            )
            ->addOption(
                'job',
                'j',
                InputOption::VALUE_REQUIRED,
                'If set, the stats will pulled specifically for this job.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $headers = [
            'name'                  => 'Tube',
            'current-jobs-urgent'   => 'JobsUrgent',
            'current-jobs-ready'    => 'JobsReady',
            'current-jobs-reserved' => 'JobsReserved',
            'current-jobs-delayed'  => 'JobsDelayed',
            'current-jobs-buried'   => 'JobsBuried',
            'total-jobs'            => 'Jobs',
            'current-using'         => 'Using',
            'current-watching'      => 'Watching',
            'current-waiting'       => 'Waiting',
            'cmd-delete'            => 'Delete',
            'cmd-pause-tube'        => 'PauseTube',
            'pause'                 => 'Pause',
            'pause-time-left'       => 'PauseTimeLeft'
        ];

        if ($input->getOption('tube')) {
            $tube = $input->getOption('tube');
            $this->displayForTube($output, $headers, $tube);
        } elseif ($input->getOption('job')) {
            $job = $input->getOption('job');
            $this->displayForJob($output, $job);
        } else {
            $this->displayAll($output, $headers);
        }
    }

    protected function displayAll($output, $headers)
    {
        $beanstalk = $this->getBeanstalk();

        $this->displayAllTopLevel($output, $beanstalk);
        $this->displayAllTubes($output, $beanstalk, $headers);
    }

    protected function displayAllTopLevel($output, $beanstalk)
    {
        //        $formatter = $this->getHelper('formatter');
        //        $formattedBlock = $formatter->formatBlock(['Some Beanstalk Title', 'Some version and pid information'], 'info');
        //        $output->writeLn($formattedBlock);

        $output->writeLn('<info>Global</info>');
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
            'job-timeouts' => 'JobTimeoues',
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

    protected function displayAllTubes($output, $beanstalk, $headers)
    {
        $table = $this->getTable($output);
        $table->setHeaders(array_values($headers));
        $output->writeLn('<info>Tubes</info>');
        $tubes = $beanstalk->listTubes();
        foreach ($tubes as $tube) {
            $stats = $beanstalk->statsTube($tube);
            $table->addRow($stats);
        }
        $table->render();
    }

    protected function displayForTube($output, $headers, $tube)
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

    protected function getBeanstalk()
    {
        return new \Phlib\Beanstalk\Beanstalk('localhost');
    }
}
