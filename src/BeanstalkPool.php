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
            foreach ($this->randomKeys() as $key) {
                try {
                    $result = $this->sendToExact($key, 'reserve', [0]);
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
        if (!isset($this->watching[$tube])) {
            $this->sendToAll('watch', [$tube]);
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
            $this->sendToAll('ignore', [$tube]);
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
        return $this->sendToExact($key, 'peek', [$jobId])['response'];
    }

    /**
     * @return array|null
     */
    public function peekReady()
    {
        return $this->sendToRandom('peekReady')['response'];
    }

    /**
     * @return array|null
     */
    public function peekDelayed()
    {
        return $this->sendToRandom('peekDelayed')['response'];
    }

    /**
     * @return array|null
     */
    public function peekBuried()
    {
        return $this->sendToRandom('peekBuried')['response'];
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

                $stats = $result['response'];
                $buriedJobs = isset($stats['current-jobs-buried'])
                    ? $stats['current-jobs-buried'] : 0;

                if ($buriedJobs > 0) {
                    $kick   = min($buriedJobs, $quantity - $kicked);
                    $result = $this->sendToExact($key, 'kick', [$kick]);
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
        $results = $this->sendToAll('stats');
        foreach ($results as $result) {
            if (!isset($result['response']) || !is_array($result['response'])) {
                continue;
            }
            $stats = $this->statsCombine($stats, $result['response']);
        }

        if (is_array($stats)) {
            $keys = [
                'current-jobs-urgent',
                'current-jobs-ready',
                'current-jobs-reserved',
                'current-jobs-delayed',
                'current-jobs-buried',
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
                'job-timeouts',
                'total-jobs',
                'current-tubes',
                'current-connections',
                'current-producers',
                'current-workers',
                'current-waiting',
                'total-connections',
            ];

            return array_intersect_key($stats, array_flip($keys));
        }

        return false;
    }

    /**
     * @param string|integer $id
     * @return array
     */
    public function statsJob($id)
    {
        list($key, $jobId) = $this->splitId($id);
        return $this->sendToExact($key, 'statsJob', [$jobId])['response'];
    }

    /**
     * @param string $tube
     * @return array
     */
    public function statsTube($tube)
    {
        $stats = [];
        $results = $this->sendToAll('statsTube', [$tube]);
        foreach ($results as $result) {
            if (!isset($result['response']) || !is_array($result['response'])) {
                continue;
            }
            $stats = $this->statsCombine($stats, $result['response']);
        }

        if (empty($stats)) {
            return false;
        }

        $keys = [
            'current-jobs-urgent',
            'current-jobs-ready',
            'current-jobs-reserved',
            'current-jobs-delayed',
            'current-jobs-buried',
            'total-jobs',
            'current-using',
            'current-watching',
            'current-waiting',
            'cmd-delete',
            'cmd-pause-tube',
            'pause',
            'pause-time-left',
        ];

        return array_intersect_key($stats, array_flip($keys));
    }

    /**
     * @param array $cumulative
     * @param array $stats
     * @return array
     */
    protected function statsCombine(array $cumulative, array $stats)
    {
        foreach ($stats as $name => $value) {
            if (!array_key_exists($name, $cumulative)) {
                $cumulative[$name] = 0;
            }
            $cumulative[$name] += $value;
        }
        return $cumulative;
    }

    /**
     * @return array
     */
    public function listTubes()
    {
        $tubes = [];
        $responses = $this->sendToAll('listTubes');
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
     * @param Beanstalk      $connection
     * @param integer|string $id
     * @return string
     */
    protected function combineId(Beanstalk $connection, $id)
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
     * @throws \Exception
     * @throws RuntimeException
     */
    public function sendToRandom($command, array $arguments = [])
    {
        $e = null;
        foreach ($this->randomKeys() as $key) {
            try {
                $result = $this->sendToExact($key, $command, $arguments);
                if ($result['response'] !== false) {
                    return $result;
                }
            } catch (RuntimeException $e) {
                // ignore
            }
        }

        if ($e instanceof \Exception) {
            throw $e;
        }
        return ['connection' => null, 'response' => false];
    }

    /**
     * @param string $command
     * @param array  $arguments
     * @return array
     */
    public function sendToAll($command, array $arguments = [])
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

            if (is_array($result) and array_key_exists('id', $result)) {
                $result['id'] = $this->combineId($connection, $result['id']);
            }

            return ['connection' => $connection, 'response' => $result];
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
