<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Stats;

use Phlib\Beanstalk\ConnectionInterface;

/**
 * @package Phlib\Beanstalk
 */
class Service
{
    public const SERVER_BINLOG = 1;

    public const SERVER_COMMAND = 2;

    public const SERVER_CURRENT = 4;

    public const SERVER_ALL = 7;

    public const TUBE_HEADER_MAPPING = [
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
    ];

    private const INFO_KEYS = [
        'pid',
        'hostname',
        'id',
        'version',
        'uptime',
        'total-jobs',
        'job-timeouts',
        'max-job-size',
        'total-connections',
        'rusage-utime',
        'rusage-stime',
    ];

    private const SERVER_KEYS = [
        'current-jobs-urgent',
        'current-jobs-ready',
        'current-jobs-reserved',
        'current-jobs-delayed',
        'current-jobs-buried',
        'current-tubes',
        'current-connections',
        'current-producers',
        'current-workers',
        'current-waiting',
        'cmd-put',
        'cmd-peek',
        'cmd-peek-ready',
        'cmd-peek-delayed',
        'cmd-peek-buried',
        'cmd-reserve',
        'cmd-reserve-with-timeout',
        'cmd-delete',
        'cmd-release',
        'cmd-use',
        'cmd-watch',
        'cmd-ignore',
        'cmd-bury',
        'cmd-kick',
        'cmd-touch',
        'cmd-stats',
        'cmd-stats-job',
        'cmd-stats-tube',
        'cmd-list-tubes',
        'cmd-list-tube-used',
        'cmd-list-tubes-watched',
        'cmd-pause-tube',
        'binlog-oldest-index',
        'binlog-current-index',
        'binlog-records-migrated',
        'binlog-records-written',
        'binlog-max-size',
    ];

    private ConnectionInterface $beanstalk;

    private array $stats;

    public function __construct(ConnectionInterface $beanstalk)
    {
        $this->beanstalk = $beanstalk;
    }

    public function getServerInfo(): array
    {
        return $this->filterTheseKeys(self::INFO_KEYS, $this->getStats());
    }

    public function getServerStats(int $filter = self::SERVER_ALL): array
    {
        $serverKeys = $this->filterServerKeys($filter);
        $stats = $this->filterTheseKeys($serverKeys, $this->getStats());
        return $stats;
    }

    private function filterServerKeys(int $filter): array
    {
        $serverKeys = self::SERVER_KEYS;
        if ($filter !== self::SERVER_ALL) {
            $include = [];
            if ($filter & self::SERVER_BINLOG) {
                $include[] = 'binlog-';
            }
            if ($filter & self::SERVER_CURRENT) {
                $include[] = 'current-';
            }
            if ($filter & self::SERVER_COMMAND) {
                $include[] = 'cmd-';
            }
            $filtered = [];
            foreach ($include as $beginsWith) {
                foreach ($serverKeys as $serverKey) {
                    if (substr($serverKey, 0, strlen($beginsWith)) !== $beginsWith) {
                        continue;
                    }
                    $filtered[] = $serverKey;
                }
            }
            return $filtered;
        }
        return $serverKeys;
    }

    public function getTubeStats(string $tube): array
    {
        $stats = $this->beanstalk->statsTube($tube);
        unset($stats['name']);
        return $stats;
    }

    public function getAllTubeStats(): array
    {
        $tubes = $this->beanstalk->listTubes();
        $tubeStats = [];
        foreach ($tubes as $tube) {
            $stats = $this->beanstalk->statsTube($tube);
            /**
             * Remove last 3 entries (for 'pause') to match stats in
             * @see StatsService::TUBE_HEADER_MAPPING
             */
            $tubeStats[] = array_slice($stats, 0, -3);
        }

        usort($tubeStats, function ($a, $b) {
            return ($a['name'] < $b['name']) ? -1 : 1;
        });

        return $tubeStats;
    }

    private function filterTheseKeys(array $keys, array $data): array
    {
        return array_intersect_key($data, array_flip($keys));
    }

    private function getStats(): array
    {
        if (!isset($this->stats)) {
            $this->stats = $this->beanstalk->stats();
        }
        return $this->stats;
    }
}
