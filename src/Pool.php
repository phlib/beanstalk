<?php

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
     * @var string
     */
    protected $using = Connection::DEFAULT_TUBE;

    /**
     * @var array
     */
    protected $watching = [Connection::DEFAULT_TUBE => true];

    /**
     * @param CollectionInterface $collection
     */
    public function __construct(CollectionInterface $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        $result = true;
        /** @var ConnectionInterface $connection */
        foreach ($this->collection as $connection) {
            $result = $result && $connection->disconnect();
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return __CLASS__;
    }

    /**
     * @return CollectionInterface
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param string $tube
     * @return $this
     */
    public function useTube($tube)
    {
        $this->collection->sendToAll('useTube', [$tube]);
        $this->using = $tube;
        return $this;
    }

    /**
     * @param string   $data
     * @param integer $priority
     * @param integer $delay
     * @param integer $ttr
     * @return string
     */
    public function put(
        $data,
        $priority = self::DEFAULT_PRIORITY,
        $delay = self::DEFAULT_DELAY,
        $ttr = self::DEFAULT_TTR
    ) {
        $result = $this->collection->sendToOne('put', func_get_args());
        if (!$result['connection'] instanceof ConnectionInterface || $result['response'] === false) {
            throw new RuntimeException('Failed to put the job into the tube.');
        }
        return $this->combineId($result['connection'], $result['response']);
    }

    /**
     * @param integer $timeout
     * @return array|false
     */
    public function reserve($timeout = null)
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
     * @return $this
     */
    public function touch($id)
    {
        list($key, $jobId) = $this->splitId($id);
        $this->collection->sendToExact($key, 'touch', [$jobId]);
        return $this;
    }

    /**
     * @param string  $id
     * @param integer $priority
     * @param integer $delay
     * @return $this
     */
    public function release($id, $priority = self::DEFAULT_PRIORITY, $delay = self::DEFAULT_DELAY)
    {
        list($key, $jobId) = $this->splitId($id);
        $this->collection->sendToExact($key, 'release', [$jobId, $priority, $delay]);
        return $this;
    }

    /**
     * @param string  $id
     * @param integer $priority
     * @return $this
     */
    public function bury($id, $priority = self::DEFAULT_PRIORITY)
    {
        list($key, $jobId) = $this->splitId($id);
        $this->collection->sendToExact($key, 'bury', [$jobId, $priority]);
        return $this;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function delete($id)
    {
        list($key, $jobId) = $this->splitId($id);
        $this->collection->sendToExact($key, 'delete', [$jobId]);
        return $this;
    }

    /**
     * @param string $tube
     * @return $this
     */
    public function watch($tube)
    {
        if (!isset($this->watching[$tube])) {
            $this->collection->sendToAll('watch', [$tube]);
            $this->watching[$tube] = true;
        }
        return $this;
    }

    /**
     * @param string $tube
     * @return integer|false
     */
    public function ignore($tube)
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
    public function peek($id)
    {
        list($key, $jobId) = $this->splitId($id);
        $result    = $this->collection->sendToExact($key, 'peek', [$jobId]);
        $job       = $result['response'];
        $job['id'] = $id;
        return $job;
    }

    /**
     * @return array|false
     */
    public function peekReady()
    {
        $result = $this->collection->sendToOne('peekReady');
        if (isset($result['response']['id'])) {
            $result['response']['id'] = $this->combineId($result['connection'], $result['response']['id']);
        }
        return $result['response'];
    }

    /**
     * @return array|false
     */
    public function peekDelayed()
    {
        $result = $this->collection->sendToOne('peekDelayed');
        if (isset($result['response']['id'])) {
            $result['response']['id'] = $this->combineId($result['connection'], $result['response']['id']);
        }
        return $result['response'];
    }

    /**
     * @return array|false
     */
    public function peekBuried()
    {
        $result = $this->collection->sendToOne('peekBuried');
        if (isset($result['response']['id'])) {
            $result['response']['id'] = $this->combineId($result['connection'], $result['response']['id']);
        }
        return $result['response'];
    }

    /**
     * @param integer $quantity
     * @return integer
     */
    public function kick($quantity)
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
            $result = $result['connection']->kick($kick);
            $kicked += (int)$result['response'];

            if ($kicked >= $quantity) {
                return false;
            }
        };
        $this->collection->sendToAll('statsTube', [$this->using], $onSuccess);

        return $kicked;
    }

    /**
     * @return array
     */
    public function stats()
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
    public function statsJob($id)
    {
        list($key, $jobId) = $this->splitId($id);
        $result    = $this->collection->sendToExact($key, 'statsJob', [$jobId]);
        $job       = $result['response'];
        $job['id'] = $id;
        return $job;
    }

    /**
     * @param string $tube
     * @return array
     */
    public function statsTube($tube)
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
     * @return array
     */
    public function listTubes()
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
     * @return string
     */
    public function listTubeUsed()
    {
        return $this->using;
    }

    /**
     * @return array
     */
    public function listTubesWatched()
    {
        return array_keys($this->watching);
    }

    /**
     * @param ConnectionInterface $connection
     * @param integer $id
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
