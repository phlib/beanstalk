<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Model\Stats;
use Phlib\Beanstalk\Pool\ManagedConnection;

/**
 * @package Phlib\Beanstalk
 */
class Pool implements ConnectionInterface
{
    /**
     * @var ManagedConnection[]
     */
    private array $connections = [];

    private string $using = Connection::DEFAULT_TUBE;

    private array $watching = [
        Connection::DEFAULT_TUBE => true,
    ];

    private int $retryDelay;

    /**
     * @param ConnectionInterface[] $connections
     */
    public function __construct(array $connections, int $retryDelay = 600)
    {
        $this->retryDelay = $retryDelay;

        if (empty($connections)) {
            throw new InvalidArgumentException('Connections for Pool are empty');
        }
        $this->addConnections($connections);
    }

    private function addConnections(array $connections): void
    {
        foreach ($connections as $connection) {
            $this->addConnection($connection);
        }
    }

    private function addConnection(ConnectionInterface $connection): void
    {
        $key = $connection->getName();
        if (array_key_exists($key, $this->connections)) {
            throw new InvalidArgumentException("Specified connection '{$key}' already exists.");
        }

        $this->connections[$key] = new ManagedConnection($connection, $this->retryDelay);
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

    public function disconnect(): bool
    {
        $result = true;
        foreach ($this->connections as $connection) {
            $result = $result && $connection->disconnect();
        }
        return $result;
    }

    public function getName(): string
    {
        return spl_object_hash($this);
    }

    public function useTube(string $tube): void
    {
        foreach ($this->getAvailableConnections() as $connection) {
            try {
                $connection->useTube($tube);
            } catch (RuntimeException $e) {
                // ignore connections not responding
            }
        }

        $this->using = $tube;
    }

    public function put(
        string $data,
        int $priority = self::DEFAULT_PRIORITY,
        int $delay = self::DEFAULT_DELAY,
        int $ttr = self::DEFAULT_TTR
    ): string {
        return $this->sendToOne(
            function (ConnectionInterface $connection, string $key) use ($data, $priority, $delay, $ttr): string {
                $jobId = $connection->put($data, $priority, $delay, $ttr);
                return $this->combineId($key, $jobId);
            }
        );
    }

    public function reserve(?int $timeout = null): array
    {
        $startTime = time();
        do {
            foreach ($this->getAvailableConnections() as $key => $connection) {
                try {
                    // override timeout, return as quickly as possible
                    $result = $connection->reserve(0);

                    $result['id'] = $this->combineId($key, (int)$result['id']);
                    return $result;
                } catch (NotFoundException | RuntimeException $e) {
                    // ignore connections not responding
                }
            }

            usleep(25 * 1000);
            $timeTaken = time() - $startTime;
        } while ($timeTaken < $timeout);

        throw new NotFoundException(
            NotFoundException::RESERVE_NO_JOBS_AVAILABLE_MSG,
            NotFoundException::RESERVE_NO_JOBS_AVAILABLE_CODE,
        );
    }

    /**
     * @param string $id
     */
    public function touch($id): void
    {
        [$connection, $jobId] = $this->splitId($id);
        $connection->touch($jobId);
    }

    /**
     * @param string $id
     */
    public function release($id, int $priority = self::DEFAULT_PRIORITY, int $delay = self::DEFAULT_DELAY): void
    {
        [$connection, $jobId] = $this->splitId($id);
        $connection->release($jobId, $priority, $delay);
    }

    /**
     * @param string $id
     */
    public function bury($id, int $priority = self::DEFAULT_PRIORITY): void
    {
        [$connection, $jobId] = $this->splitId($id);
        $connection->bury($jobId, $priority);
    }

    /**
     * @param string $id
     */
    public function delete($id): void
    {
        [$connection, $jobId] = $this->splitId($id);
        $connection->delete($jobId);
    }

    public function watch(string $tube): int
    {
        if (!isset($this->watching[$tube])) {
            foreach ($this->getAvailableConnections() as $connection) {
                try {
                    $connection->watch($tube);
                } catch (RuntimeException $e) {
                    // ignore connections not responding
                }
            }

            $this->watching[$tube] = true;
        }

        return count($this->watching);
    }

    public function ignore(string $tube): int
    {
        if (isset($this->watching[$tube])) {
            if (count($this->watching) === 1) {
                throw new CommandException('Cannot ignore the only tube in the watch list');
            }

            foreach ($this->getAvailableConnections() as $connection) {
                try {
                    $connection->ignore($tube);
                } catch (RuntimeException $e) {
                    // ignore connections not responding
                }
            }

            unset($this->watching[$tube]);
        }
        return count($this->watching);
    }

    /**
     * @param string $id
     */
    public function peek($id): array
    {
        [$connection, $jobId] = $this->splitId($id);

        $job = $connection->peek($jobId);
        $job['id'] = $id;

        return $job;
    }

    public function peekReady(): array
    {
        return $this->peekStatus('peekReady');
    }

    public function peekDelayed(): array
    {
        return $this->peekStatus('peekDelayed');
    }

    public function peekBuried(): array
    {
        return $this->peekStatus('peekBuried');
    }

    private function peekStatus(string $command): array
    {
        return $this->sendToOne(function (ConnectionInterface $connection, string $key) use ($command): array {
            $job = $connection->{$command}();
            $job['id'] = $this->combineId($key, (int)$job['id']);
            return $job;
        });
    }

    public function kick(int $quantity): int
    {
        $kicked = 0;
        foreach ($this->getAvailableConnections() as $connection) {
            try {
                $stats = $connection->statsTube($this->using);

                $buriedJobs = $stats['current-jobs-buried'] ?? 0;

                if ($buriedJobs === 0) {
                    continue;
                }

                $kick = min($buriedJobs, $quantity - $kicked);
                $result = $connection->kick($kick);
                $kicked += $result;

                if ($kicked >= $quantity) {
                    break;
                }
            } catch (RuntimeException $e) {
                // ignore connections not responding
            }
        }

        return $kicked;
    }

    /**
     * @param string $id
     */
    public function statsJob($id): array
    {
        [$connection, $jobId] = $this->splitId($id);

        $job = $connection->statsJob($jobId);
        $job['id'] = $id;

        return $job;
    }

    public function statsTube(string $tube): array
    {
        $stats = new Stats();
        foreach ($this->getAvailableConnections() as $connection) {
            try {
                $result = $connection->statsTube($tube);
                $stats = $stats->aggregate($result);
            } catch (NotFoundException | RuntimeException $e) {
                // ignore connections not responding or without the given tube
            }
        }

        return $stats->toArray();
    }

    public function stats(): array
    {
        $stats = new Stats();
        foreach ($this->getAvailableConnections() as $connection) {
            try {
                $result = $connection->stats();
                $stats = $stats->aggregate($result);
            } catch (RuntimeException $e) {
                // ignore connections not responding
            }
        }

        return $stats->toArray();
    }

    public function listTubes(): array
    {
        $tubes = [];
        foreach ($this->getAvailableConnections() as $connection) {
            try {
                $result = $connection->listTubes();
                $tubes = array_merge($result, $tubes);
            } catch (RuntimeException $e) {
                // ignore
            }
        }
        return array_unique($tubes);
    }

    public function listTubeUsed(): string
    {
        return $this->using;
    }

    public function listTubesWatched(): array
    {
        return array_keys($this->watching);
    }

    private function combineId(string $connectionKey, int $jobId): string
    {
        return $connectionKey . '.' . $jobId;
    }

    private function splitId(string $id): array
    {
        if (strpos($id, '.') === false) {
            throw new InvalidArgumentException('Job ID is not in expected pool format.');
        }

        $position = strrpos($id, '.');
        $key = substr($id, 0, $position);
        $jobId = (int)substr($id, $position + 1);

        if (!isset($this->connections[$key])) {
            throw new InvalidArgumentException("Specified connection '{$key}' is not in the pool");
        }

        $connection = $this->connections[$key];
        if (!$connection->isAvailable()) {
            throw new RuntimeException("Specified connection '{$key}' is not currently available");
        }

        return [$connection, $jobId];
    }

    /**
     * @return array|int|string
     */
    private function sendToOne(\Closure $commandFn)
    {
        foreach ($this->getAvailableConnections() as $key => $connection) {
            try {
                return $commandFn($connection, $key);
            } catch (NotFoundException | RuntimeException $e) {
                // ignore and try a different connection
            }
        }

        if (isset($e) && $e instanceof NotFoundException) {
            throw $e;
        }

        throw new RuntimeException(
            'Failed to send command to one of the available servers in the pool',
            0,
            $e ?? null,
        );
    }

    /**
     * @return \Generator<ManagedConnection>
     */
    private function getAvailableConnections(): \Generator
    {
        $keys = array_keys($this->connections);
        shuffle($keys);
        foreach ($keys as $key) {
            $connection = $this->connections[$key];
            if (!$connection->isAvailable()) {
                continue;
            }
            yield $key => $connection;
        }
    }
}
