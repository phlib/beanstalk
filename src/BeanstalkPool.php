<?php

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\RuntimeException;

class BeanstalkPool implements BeanstalkInterface
{
    /**
     * @var Beanstalk[]
     */
    protected $connections;

    /**
     * @var string
     */
    protected $using = Beanstalk::DEFAULT_TUBE;

    /**
     * @var array
     */
    protected $watching = [Beanstalk::DEFAULT_TUBE => true];

    /**
     * @param Beanstalk[] $connections
     */
    public function __construct(array $connections)
    {
        $formatted = [];
        foreach ($connections as $connection) {
            if (!$connection instanceof Beanstalk) {
                throw new InvalidArgumentException('Invalid connection specified for pool.');
            }
            $key = $connection->getUniqueIdentifier();
            $formatted[$key] = [
                'connection' => $connection,
                'retry_at'   => false
            ];
        }
        $this->connections = $formatted;
    }

    /**
     * @param string $tube
     * @return $this
     */
    public function useTube($tube)
    {
        $this->sendToAll('useTube', [$tube]);
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
        $result = $this->sendToRandom('put', func_get_args());
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
            $connections = $this->getRandomisedConnections();
            foreach ($connections as $connection) {
                try {
                    $result = $connection->reserve(0);
                    if ($result['response']) {
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
        $this->sendToExact($key, 'touch', [$jobId]);
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
        $this->sendToExact($key, 'release', [$jobId, $priority, $delay]);
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
        $this->sendToExact($key, 'bury', [$jobId, $priority]);
        return $this;
    }

    /**
     * @param string|integer $id
     * @return $this
     */
    public function delete($id)
    {
        list($key, $jobId) = $this->splitId($id);
        $this->sendToExact($key, 'delete', [$jobId]);
        return $this;
    }

    /**
     * @param string $tube
     * @return $this
     */
    public function watch($tube)
    {
        if (!in_array($tube, $this->watching)) {
            $this->sendToAll('watch', [$tube]);
            $this->watching[] = $tube;
        }
        return $this;
    }

    /**
     * @param string $tube
     * @return $this
     */
    public function ignore($tube)
    {
        $index = array_search($tube, $this->watching);
        if ($index !== false) {
            $this->sendToAll('ignore', [$tube]);
            unset($this->watching[$index]);
        }
        return $this;
    }

    /**
     * @param string|integer $id
     * @return array
     */
    public function peek($id)
    {
        list($key, $jobId) = $this->splitId($id);
        return $this->sendToExact($key, 'peek', [$jobId]);
    }

    /**
     * @return array|null
     */
    public function peekReady()
    {
        return $this->pullFromRandom('peekReady');
    }

    /**
     * @return array|null
     */
    public function peekDelayed()
    {
        return $this->pullFromRandom('peekDelayed');
    }

    /**
     * @return array|null
     */
    public function peekBuried()
    {
        return $this->pullFromRandom('peekBuried');
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
                $result = $this->sendToExact($key, 'statsTube', [$this->using]);

                $buriedJobs = isset($result['current-jobs-buried'])
                    ? $result['current-jobs-buried'] : 0;

                if ($buriedJobs > 0) {
                    $kick   = min($buriedJobs, $quantity - $kicked);
                    $result = $this->sendToExact($key, 'kick', [$kick]);
                    $kicked += (int)$result;

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
        // TODO: Implement stats() method.
    }

    /**
     * @param string|integer $id
     * @return array
     */
    public function statsJob($id)
    {
        // TODO: Implement statsJob() method.
    }

    /**
     * @param string $tube
     * @return array
     */
    public function statsTube($tube)
    {
        // TODO: Implement statsTube() method.
    }

    /**
     * @return array
     */
    public function listTubes()
    {
        $tubes = [];
        foreach ($this->randKeyList() as $key) {
            try {
                $listTubes = $this->formatResponse($this->sendCommand($key, 'listTubes'));
                $tubes = array_merge($listTubes, $tubes);
            } catch (\Exception $e) {
                // ignore
            }
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
        return $this->watching;
    }

    /**
     * @param Beanstalk      $connection
     * @param integer|string $id
     * @return string
     */
    protected function combineId(Beanstalk $connection, $id)
    {
        $socket = $connection->getSocket();
        return "{$socket->getUniqueIdentifier()}.{$id}";
    }

    /**
     * @param string $id
     * @return array Indexed array of key and id.
     */
    protected function splitId($id)
    {
        list($key, $jobId) = explode('.', $id, 3);
        return [$key, $jobId];
    }

    /**
     * @param string $key
     * @return Beanstalk
     * @throws NotFoundException
     * @throws RuntimeException
     */
    public function getConnection($key)
    {
        if (!array_key_exists($key, $this->connections)) {
            throw new NotFoundException("Specified key '$key' is not in the pool.");
        }
        $retryAt = $this->connections[$key]['retry_at'];
        if ($retryAt !== false && $retryAt > time()) {
            throw new RuntimeException('Connection recently failed.');
        }
        return $this->connections[$key]['connection'];
    }

    /**
     * @param string $command
     * @param array  $arguments
     * @return mixed
     * @throws RuntimeException
     */
    public function sendToRandom($command, array $arguments)
    {
        foreach ($this->randomKeys() as $key) {
            try {
                return $this->sendToExact($key, $command, $arguments);
            } catch (RuntimeException $e) {
                // ignore
            }
        }

        if (!isset($e) || !$e instanceof \Exception) {
            $e = new RuntimeException('Failed to execute command to a connection.');
        }
        throw $e;
    }

    /**
     * @param string $command
     * @param array  $arguments
     * @return array
     */
    public function sendToAll($command, array $arguments)
    {
        $results = [];
        $keys = array_keys($this->connections);
        foreach ($keys as $key) {
            try {
                $results[$key] = $this->sendToExact($key, $command, $arguments);
            } catch (\Exception $e) {
                // ignore
            }
        }
        return $results;
    }

    /**
     * @param string $key
     * @param string $command
     * @param array  $arguments
     * @return mixed
     * @throws NotFoundException
     */
    public function sendToExact($key, $command, array $arguments = [])
    {
        try {
            $connection = $this->getConnection($key);
            $result     = call_user_func_array([$connection, $command], $arguments);
            $this->connections[$key]['retry_at'] = false;
            return $result;
        } catch (RuntimeException $e) {
            if ($this->connections[$key]['retry_at'] === false) {
                $this->connections[$key]['retry_at'] = time() + 600; // 10 minutes
            }
            throw $e;
        }
    }

    /**
     * @return array
     */
    protected function randomKeys()
    {
        $keys = array_keys($this->connections);
        shuffle($keys);
        return $keys;
    }
}
