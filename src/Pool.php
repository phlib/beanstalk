<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Pool\CollectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class Pool implements ConnectionInterface
{
    /**
     * @var CollectionInterface
     */
    protected $collection;

    /**
     * @var ConnectionInterface[]
     */
    protected $connections = [];

    /**
     * @var string
     */
    protected $using = Connection::DEFAULT_TUBE;

    /**
     * @var array
     */
    protected $watching = [Connection::DEFAULT_TUBE => true];

    /**
     * @param ConnectionInterface[] $connections
     */
    public function __construct(array $connections)
    {
        if (empty($connections)) {
            throw new InvalidArgumentException('Specified connections for pool is empty.');
        }
        $this->addConnections($connections);
    }

    /**
     * @param array $connections
     * @return Pool
     */
    public function addConnections(array $connections): self
    {
        foreach ($connections as $connection) {
            $this->addConnection($connection);
        }
        return $this;
    }

    /**
     * @param ConnectionInterface $connection
     * @return Pool
     */
    public function addConnection(ConnectionInterface $connection): self
    {
        $this->connections[] = $connection;
        return $this;
    }

    /**
     * @return array
     */
    public function getConnections(): array
    {
        return $this->collection;
    }

    /**
     * @inheritdoc
     */
    public function disconnect(): bool
    {
        $result = true;
        foreach ($this->connections as $connection) {
            $result = $result && $connection->disconnect();
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return spl_object_hash($this);
    }

    /**
     * @inheritdoc
     */
    public function useTube(string $tube): ConnectionInterface
    {
        $this->collection->sendToAll('useTube', [$tube]);
        $this->using = $tube;
        return $this;
    }

    /**
     * @param string $data
     * @param int $priority
     * @param int $delay
     * @param int $ttr
     * @return string
     */
    public function put(
        string $data,
        int $priority = self::DEFAULT_PRIORITY,
        int $delay = self::DEFAULT_DELAY,
        int $ttr = self::DEFAULT_TTR
    ) {
        $result = $this->collection->sendToOne('put', func_get_args());
        if (!$result['connection'] instanceof ConnectionInterface || $result['response'] === false) {
            throw new RuntimeException('Failed to put the job into the tube.');
        }
        return $this->combineId($result['connection'], $result['response']);
    }

    /**
     * @inheritdoc
     */
    public function reserve(int $timeout = null)
    {
        $startTime = time();
        do {
            /** @var \ArrayIterator $connections */
            $keys = $this->collection->getAvailableKeys();
            shuffle($keys);
            foreach ($keys as $key) {
                try {
                    $result = $this->collection->sendToExact($key, 'reserve', [0]);
                    if ($result['response'] === false) {
                        continue;
                    }

                    $result['response']['id'] = $this->combineId($result['connection'], $result['response']['id']);
                    return $result['response'];
                } catch (RuntimeException $e) {
                    // ignore servers not responding
                }
            }

            usleep(25 * 1000);
        } while (time() - $startTime < $timeout);

        return false;
    }

    /**
     * @param string $id
     * @return ConnectionInterface
     */
    public function touch($id): ConnectionInterface
    {
        list($key, $jobId) = $this->splitId($id);
        $this->collection->sendToExact($key, 'touch', [$jobId]);
        return $this;
    }

    /**
     * @param string $id
     * @param int $priority
     * @param int $delay
     * @return ConnectionInterface
     */
    public function release(
        $id,
        int $priority = self::DEFAULT_PRIORITY,
        int $delay = self::DEFAULT_DELAY
    ): ConnectionInterface {
        list($key, $jobId) = $this->splitId($id);
        $this->collection->sendToExact($key, 'release', [$jobId, $priority, $delay]);
        return $this;
    }

    /**
     * @param string $id
     * @param int $priority
     * @return ConnectionInterface
     */
    public function bury($id, int $priority = self::DEFAULT_PRIORITY): ConnectionInterface
    {
        list($key, $jobId) = $this->splitId($id);
        $this->collection->sendToExact($key, 'bury', [$jobId, $priority]);
        return $this;
    }

    /**
     * @param string $id
     * @return ConnectionInterface
     */
    public function delete($id): ConnectionInterface
    {
        list($key, $jobId) = $this->splitId($id);
        $this->collection->sendToExact($key, 'delete', [$jobId]);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function watch(string $tube): ConnectionInterface
    {
        if (!isset($this->watching[$tube])) {
            $this->collection->sendToAll('watch', [$tube]);
            $this->watching[$tube] = true;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function ignore(string $tube)
    {
        $index = array_search($tube, $this->watching);
        if ($index !== false) {
            if (count($this->watching) == 1) {
                return false;
            }
            $this->collection->sendToAll('ignore', [$tube]);
            unset($this->watching[$index]);
        }
        return count($this->watching);
    }

    /**
     * @param string $id
     * @return array
     */
    public function peek($id): array
    {
        list($key, $jobId) = $this->splitId($id);
        $result    = $this->collection->sendToExact($key, 'peek', [$jobId]);
        $job       = $result['response'];
        $job['id'] = $id;
        return $job;
    }

    /**
     * @inheritdoc
     */
    public function peekReady()
    {
        return $this->peekStatus('peekReady');
    }

    /**
     * @inheritdoc
     */
    public function peekDelayed()
    {
        return $this->peekStatus('peekDelayed');
    }

    /**
     * @inheritdoc
     */
    public function peekBuried()
    {
        return $this->peekStatus('peekBuried');
    }

    /**
     * @param string $command
     * @return array|false
     */
    protected function peekStatus($command)
    {
        try {
            $result = $this->collection->sendToOne($command, [], true);
        } catch (RuntimeException $e) {
            return false;
        }
        if (isset($result['response']) && is_array($result['response']) && isset($result['response']['id'])) {
            $result['response']['id'] = $this->combineId($result['connection'], $result['response']['id']);
        }
        return $result['response'];
    }

    /**
     * @inheritdoc
     */
    public function kick(int $quantity): int
    {
        $kicked    = 0;
        $onSuccess = function ($result) use ($quantity, &$kicked) {
            $stats = $result['response'];
            $buriedJobs = isset($stats['current-jobs-buried'])
                ? $stats['current-jobs-buried'] : 0;

            if ($buriedJobs == 0) {
                return true;
            }

            $kick   = min($buriedJobs, $quantity - $kicked);
            $kicked += (int)$result['connection']->kick($kick);

            if ($kicked >= $quantity) {
                return false;
            }
        };
        $this->collection->sendToAll('statsTube', [$this->using], $onSuccess);

        return $kicked;
    }

    /**
     * @inheritdoc
     */
    public function stats(): array
    {
        $stats     = [];
        $onSuccess = function ($result) use (&$stats) {
            if (!is_array($result['response'])) {
                return;
            }
            $stats = $this->statsCombine($stats, $result['response']);
        };
        $this->collection->sendToAll('stats', [], $onSuccess);

        if (!is_array($stats) || empty($stats)) {
            return false;
        }
        return $stats;
    }

    /**
     * @param string $id
     * @return array
     */
    public function statsJob($id): array
    {
        list($key, $jobId) = $this->splitId($id);
        $result    = $this->collection->sendToExact($key, 'statsJob', [$jobId]);
        $job       = $result['response'];
        $job['id'] = $id;
        return $job;
    }

    /**
     * @inheritdoc
     */
    public function statsTube(string $tube): array
    {
        $stats     = [];
        $onSuccess = function ($result) use (&$stats) {
            if (!is_array($result['response'])) {
                return;
            }
            $stats = $this->statsCombine($stats, $result['response']);
        };
        $this->collection->sendToAll('statsTube', [$tube], $onSuccess);

        if (!is_array($stats) || empty($stats)) {
            return false;
        }
        return $stats;
    }

    /**
     * @param array $cumulative
     * @param array $stats
     * @return array
     */
    protected function statsCombine(array $cumulative, array $stats)
    {
        $list    = ['pid', 'version', 'hostname', 'name', 'uptime', 'binlog-current-index'];
        $maximum = ['timeouts', 'binlog-max-size', 'binlog-oldest-index'];
        foreach ($stats as $name => $value) {
            if (!array_key_exists($name, $cumulative)) {
                $cumulative[$name] = $value;
                continue;
            }

            if (in_array($name, $list)) {
                if ($cumulative[$name] != $value) {
                    $cumulative[$name] .= ',' . $value;
                }
            } elseif (in_array($name, $maximum)) {
                if ($value > $cumulative[$name]) {
                    $cumulative[$name] = $value;
                }
            } else {
                $cumulative[$name] += $value;
            }
        }
        return $cumulative;
    }

    /**
     * @inheritdoc
     */
    public function listTubes(): array
    {
        $tubes     = [];
        $onSuccess = function ($result) use (&$tubes) {
            if (!is_array($result['response'])) {
                return;
            }
            $tubes = array_merge($result['response'], $tubes);
        };
        $this->collection->sendToAll('listTubes', [], $onSuccess);
        return array_unique($tubes);
    }

    /**
     * @inheritdoc
     */
    public function listTubeUsed(): string
    {
        return $this->using;
    }

    /**
     * @inheritdoc
     */
    public function listTubesWatched(): array
    {
        return array_keys($this->watching);
    }

    /**
     * @param ConnectionInterface $connection
     * @param int $id
     * @return string
     */
    public function combineId(ConnectionInterface $connection, $id)
    {
        if (!is_numeric($id)) {
            throw new InvalidArgumentException('Specified job id must be a number.');
        }
        return "{$connection->getName()}.{$id}";
    }

    /**
     * @param string $id
     * @return array Indexed array of key and id.
     * @throws InvalidArgumentException
     */
    public function splitId($id)
    {
        if (strpos($id, '.') === false) {
            throw new InvalidArgumentException('Job ID is not in expected pool format.');
        }

        $position = strrpos($id, '.');
        $key      = substr($id, 0, $position);
        $jobId    = substr($id, $position + 1);

        return [$key, $jobId];
    }
}
