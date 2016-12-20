<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Pool\ManagedConnection;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class Pool implements ConnectionInterface
{
    /**
     * @var ManagedConnection[]
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
     * @var array
     */
    private $options;

    /**
     * @param ConnectionInterface[] $connections
     * @param array $options
     */
    public function __construct(array $connections, array $options = [])
    {
        if (empty($connections)) {
            throw new InvalidArgumentException('Specified connections for pool is empty.');
        }
        $this->addConnections($connections);
        $this->options = $options + ['retry_delay' => 600];
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
        $key = $connection->getName();
        if (array_key_exists($key, $this->connections)) {
            throw new InvalidArgumentException("Specifed connection '{$key}' already exists.");
        }
        $this->connections[$key] = new ManagedConnection($connection, $this->options['retry_delay']);
        return $this;
    }

    /**
     * @return ConnectionInterface[]
     */
    public function getConnections(): array
    {
        $connections = [];
        foreach ($this->connections as $connection) {
            $connections[] = $connection->getConnection();
        }
        return $connections;
    }

    /**
     * @inheritdoc
     */
    public function disconnect(): bool
    {
        $result = true;
        foreach ($this->getConnections() as $connection) {
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
        $this->sendToAll('useTube', $tube);
//        foreach ($this->getAvailableConnections() as $connection) {
//            $connection->send('useTube', $tube);
//        }
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
        $result = $this->sendToOne('put', $data, $priority, $delay, $ttr);
        return $this->combineId($result['connection'], $result['response']);
    }

    /**
     * @inheritdoc
     */
    public function reserve(int $timeout = null)
    {
        $startTime = time();
        do {
            foreach ($this->getAvailableConnections() as $connection) {
                try {
                    // override timeout, return as quickly as possible
                    $result = $connection->send('reserve', 0);
                    if ($result['response'] !== false) {
                        $result['response']['id'] = $this->combineId($result['connection'], $result['response']['id']);
                        return $result['response'];
                    }
                } catch (RuntimeException $e) {
                    // ignore servers not responding
                }
            }
            usleep(25 * 1000);
            $timeTaken = time() - $startTime;
        } while ($timeTaken < $timeout);

        return false;
    }

    /**
     * @param string $id
     * @return ConnectionInterface
     */
    public function touch($id): ConnectionInterface
    {
        $this->sendToExact('touch', $id);
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
        $this->sendToExact('release', $id, $priority, $delay);
        return $this;
    }

    /**
     * @param string $id
     * @param int $priority
     * @return ConnectionInterface
     */
    public function bury($id, int $priority = self::DEFAULT_PRIORITY): ConnectionInterface
    {
        $this->sendToExact('bury', $id, $priority);
        return $this;
    }

    /**
     * @param string $id
     * @return ConnectionInterface
     */
    public function delete($id): ConnectionInterface
    {
        $this->sendToExact('delete', $id);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function watch(string $tube): ConnectionInterface
    {
        if (!isset($this->watching[$tube])) {
            $this->sendToAll('watch', $tube);
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
            $this->sendToAll('ignore', $tube);
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
        $result    = $this->sendToExact('peek', $id);
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
        foreach ($this->getAvailableConnections() as $connection) {
            try {
                $result = $connection->send($command);
                if ($result['response'] !== false) {
                    $result['response']['id'] = $this->combineId($result['connection'], $result['response']['id']);
                    return $result['response'];
                }
            } catch (RuntimeException $e) {
                // ignore
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function kick(int $quantity): int
    {
        $kicked = 0;
        foreach ($this->getAvailableConnections() as $connection) {
            try {
                $result = $connection->send('statsTube', $this->using);
                $stats  = $result['response'];

                $buriedJobs = isset($stats['current-jobs-buried'])
                    ? $stats['current-jobs-buried'] : 0;

                if ($buriedJobs == 0) {
                    continue;
                }

                $kick   = min($buriedJobs, $quantity - $kicked);
                $result = $connection->send('kick', $kick);
                $kicked += $result['response'];

                if ($kicked >= $quantity) {
                    break;
                }
            } catch (RuntimeException $e) {
                // ignore
            }
        }

        return $kicked;
    }

    /**
     * @inheritdoc
     */
    public function stats(): array
    {
        return $this->doStats('stats');
    }

    /**
     * @param string $id
     * @return array
     */
    public function statsJob($id): array
    {
        $result    = $this->sendToExact('statsJob', $id);
        $job       = $result['response'];
        $job['id'] = $id;
        return $job;
    }

    /**
     * @inheritdoc
     */
    public function statsTube(string $tube): array
    {
        return $this->doStats('statsTube', $tube);
    }

    /**
     * @param string $command
     * @param array ...$arguments
     * @return array
     */
    protected function doStats(string $command, ...$arguments): array
    {
        $stats = [];
        foreach ($this->getAvailableConnections() as $connection) {
            try {
                $result = $connection->send($command, ...$arguments);
                $stats = $this->statsCombine($stats, $result['response']);
            } catch (NotFoundException $e) {
                // ignore
            } catch (RuntimeException $e) {
                // ignore
            }
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
        $tubes = [];
        foreach ($this->getAvailableConnections() as $connection) {
            try {
                $result = $connection->send('listTubes');
                $tubes = array_merge($result['response'], $tubes);
            } catch (RuntimeException $e) {
                // ignore
            }
        }
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

    protected function sendToExact(string $command, string $id, ...$arguments)
    {
        list($key, $id) = $this->splitId($id);
        if (!array_key_exists($key, $this->connections)) {
            throw new NotFoundException("Specified key '{$key}' is not in the pool.");
        }
        $connection = $this->connections[$key];
        if (!$connection->isAvailable()) {
            throw new RuntimeException("Specified connection '{$key}' is not currently available.");
        }

        array_unshift($arguments, $id);
        return $connection->send($command, ...$arguments);
    }

    /**
     * @param string $command
     * @param array ...$arguments
     * @return array
     */
    protected function sendToOne(string $command, ...$arguments): array
    {
        foreach ($this->getAvailableConnections() as $connection) {
            try {
                $result = $connection->send($command, ...$arguments);
                if (is_array($result)) {
                    return $result;
                }
            } catch (RuntimeException $e) {
                // ignore and try a different connection
            }
        }
        throw new RuntimeException('Failed send command to one of the available servers in the pool.');
    }

    /**
     * @param string $command
     * @param array ...$arguments
     */
    protected function sendToAll(string $command, ...$arguments)
    {
        foreach ($this->getAvailableConnections() as $connection) {
            try {
                $connection->send($command, ...$arguments);
            } catch (RuntimeException $e) {
                // ignore
            }
        }
    }

    /**
     * @return \Generator
     */
    protected function getAvailableConnections(): \Generator
    {
        $keys = array_keys($this->connections);
        shuffle($keys);
        foreach ($keys as $key) {
            $connection = $this->connections[$key];
            if (!$connection->isAvailable()) {
                continue;
            }
            yield $connection;
        }
    }
}
