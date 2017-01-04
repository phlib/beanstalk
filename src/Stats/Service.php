<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Stats;

use Phlib\Beanstalk\ConnectionInterface;

class Service
{
    const SERVER_BINLOG  = 1;
    const SERVER_COMMAND = 2;
    const SERVER_CURRENT = 4;
    const SERVER_ALL     = 7;

    /**
     * @var array
     */
    protected $infoKeys = [
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

    /**
     * @var array
     */
    protected $serverKeys = [
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
    
    /**
     * @var ConnectionInterface
     */
    private $beanstalk;

    /**
     * @var array
     */
    protected $stats;

    /**
     * @param ConnectionInterface $beanstalk
     */
    public function __construct(ConnectionInterface $beanstalk)
    {
        $this->beanstalk = $beanstalk;
    }

    /**
     * @return array
     */
    public function getServerInfo(): array
    {
        return $this->filterTheseKeys($this->infoKeys, $this->getStats());
    }

    /**
     * @param int $filter
     * @return array
     */
    public function getServerStats(int $filter = self::SERVER_ALL): array
    {
        $serverKeys = $this->filterServerKeys($filter);
        $stats = $this->filterTheseKeys($serverKeys, $this->getStats());
        ksort($stats);
        return $stats;
    }

    /**
     * @param int $filter
     * @return array
     */
    protected function filterServerKeys(int $filter): array
    {
        $serverKeys = $this->serverKeys;
        if ($filter != self::SERVER_ALL) {
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
                    if (substr($serverKey, 0, strlen($beginsWith)) != $beginsWith) {
                        continue;
                    }
                    $filtered[] = $serverKey;
                }
            }
            return $filtered;
        }
        return $serverKeys;
    }

    /**
     * @param string $tube
     * @return array
     */
    public function getTubeStats(string $tube): array
    {
        $stats = $this->beanstalk->statsTube($tube);
        unset($stats['name']);
        return $stats;
    }

    /**
     * @return array
     */
    public function getAllTubeStats(): array
    {
        $tubes     = $this->beanstalk->listTubes();
        $tubeStats = [];
        foreach ($tubes as $tube) {
            $stats = $this->beanstalk->statsTube($tube);
            $tubeStats[] = array_slice($stats, 0, -3); // @see getTubeHeaderMapping
        }

        usort($tubeStats, function ($a, $b) {
            return ($a['name'] < $b['name']) ? -1 : 1;
        });

        return $tubeStats;
    }

    /**
     * @param array $keys
     * @param array $data
     * @return array
     */
    protected function filterTheseKeys(array $keys, array $data): array
    {
        return array_intersect_key($data, array_flip($keys));
    }

    /**
     * @return array
     */
    protected function getStats(): array
    {
        if (!$this->stats) {
            $this->stats = $this->beanstalk->stats();
        }
        return $this->stats;
    }

    /**
     * @return array
     */
    public function getTubeHeaderMapping(): array
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
        ];
    }
}
