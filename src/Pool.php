<?php

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
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
     * @param mixed   $data
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
            foreach ($this->randomKeys() as $key) {
                try {
                    $result = $this->collection->sendToExact($key, 'reserve', [0]);
                    if (is_array($result) && isset($result['response'])) {
                        return $result['response'];
                    }
                } catch (\Exception $e) {
                    // ignore
                }
            }
            usleep(250 * 1000);
        } while (time() - $startTime < $timeout);

        return false;
    }

    /**
     * @param string|integer $id
     * @return $this
     */
    public function touch($id)
    {
        list($key, $jobId) = $this->splitId($id);
        $this->collection->sendToExact($key, 'touch', [$jobId]);
        return $this;
    }

    /**
     * @param string|integer $id
     * @param integer        $priority
     * @param integer        $delay
     * @return $this
     */
    public function release($id, $priority = self::DEFAULT_PRIORITY, $delay = self::DEFAULT_DELAY)
    {
        list($key, $jobId) = $this->splitId($id);
        $this->collection->sendToExact($key, 'release', [$jobId, $priority, $delay]);
        return $this;
    }

    /**
     * @param string|integer $id
     * @param integer        $priority
     * @return $this
     */
    public function bury($id, $priority = self::DEFAULT_PRIORITY)
    {
        list($key, $jobId) = $this->splitId($id);
        $this->collection->sendToExact($key, 'bury', [$jobId, $priority]);
        return $this;
    }

    /**
     * @param string|integer $id
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
     * @return int|false
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
     * @param string|integer $id
     * @return array
     */
    public function peek($id)
    {
        list($key, $jobId) = $this->splitId($id);
        return $this->collection->sendToExact($key, 'peek', [$jobId])['response'];
    }

    /**
     * @return array|false
     */
    public function peekReady()
    {
        return $this->collection->sendToOne('peekReady')['response'];
    }

    /**
     * @return array|false
     */
    public function peekDelayed()
    {
        return $this->collection->sendToOne('peekDelayed')['response'];
    }

    /**
     * @return array|false
     */
    public function peekBuried()
    {
        return $this->collection->sendToOne('peekBuried')['response'];
    }

    /**
     * @param integer $quantity
     * @return integer
     */
    public function kick($quantity)
    {
        $kicked = 0;
        foreach ($this->randomKeys() as $key) {
            try {
                $result = $this->collection->sendToExact($key, 'statsTube', [$this->using]);

                $stats = $result['response'];
                $buriedJobs = isset($stats['current-jobs-buried'])
                    ? $stats['current-jobs-buried'] : 0;

                if ($buriedJobs > 0) {
                    $kick   = min($buriedJobs, $quantity - $kicked);
                    $result = $this->collection->sendToExact($key, 'kick', [$kick]);
                    $kicked += (int)$result['response'];

                    if ($kicked >= $quantity) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                // ignore
            }
        }
        return $kicked;
    }

    /**
     * @return array
     */
    public function stats()
    {
        $stats = [];
        $results = $this->collection->sendToAll('stats');
        foreach ($results as $result) {
            if (!isset($result['response']) || !is_array($result['response'])) {
                continue;
            }
            $stats = $this->statsCombine($stats, $result['response']);
        }

        if (!is_array($stats) || empty($stats)) {
            return false;
        }
        return $stats;
    }

    /**
     * @param string|integer $id
     * @return array
     */
    public function statsJob($id)
    {
        list($key, $jobId) = $this->splitId($id);
        return $this->collection->sendToExact($key, 'statsJob', [$jobId])['response'];
    }

    /**
     * @param string $tube
     * @return array
     */
    public function statsTube($tube)
    {
        $stats = [];
        $results = $this->collection->sendToAll('statsTube', [$tube]);
        foreach ($results as $result) {
            if (!isset($result['response']) || !is_array($result['response'])) {
                continue;
            }
            $stats = $this->statsCombine($stats, $result['response']);
        }

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
        $tubes = [];
        $responses = $this->collection->sendToAll('listTubes');
        foreach ($responses as $response) {
            $tubes = array_merge($response['response'], $tubes);
        }
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
     * @param Connection      $connection
     * @param integer|string $id
     * @return string
     */
    protected function combineId(Connection $connection, $id)
    {
        return "{$connection->getUniqueIdentifier()}.{$id}";
    }

    /**
     * @param string $id
     * @return array Indexed array of key and id.
     * @throws InvalidArgumentException
     */
    protected function splitId($id)
    {
        if (strpos($id, '.') === false) {
            throw new InvalidArgumentException('Job ID is not in expected pool format.');
        }
        list($key, $jobId) = explode('.', $id, 3);
        return [$key, $jobId];
    }
}
