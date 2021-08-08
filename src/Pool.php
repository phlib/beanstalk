<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Pool\CollectionInterface;

class Pool implements ConnectionInterface
{
    protected CollectionInterface $collection;

    protected string $using = Connection::DEFAULT_TUBE;

    protected array $watching = [
        Connection::DEFAULT_TUBE => true,
    ];

    public function __construct(CollectionInterface $collection)
    {
        $this->collection = $collection;
    }

    public function disconnect(): bool
    {
        $result = true;
        /** @var ConnectionInterface $connection */
        foreach ($this->collection as $connection) {
            $result = $result && $connection->disconnect();
        }
        return $result;
    }

    public function getName(): string
    {
        return __CLASS__;
    }

    public function getCollection(): CollectionInterface
    {
        return $this->collection;
    }

    public function useTube(string $tube): self
    {
        $this->collection->sendToAll('useTube', [$tube]);
        $this->using = $tube;
        return $this;
    }

    public function put(
        string $data,
        int $priority = self::DEFAULT_PRIORITY,
        int $delay = self::DEFAULT_DELAY,
        int $ttr = self::DEFAULT_TTR
    ): string {
        $result = $this->collection->sendToOne('put', func_get_args());
        if (!$result['connection'] instanceof ConnectionInterface || $result['response'] === false) {
            throw new RuntimeException('Failed to put the job into the tube.');
        }
        return $this->combineId($result['connection'], (int)$result['response']);
    }

    public function reserve(?int $timeout = null): ?array
    {
        $startTime = time();
        do {
            /** @var \ArrayIterator $connections */
            $keys = $this->collection->getAvailableKeys();
            shuffle($keys);
            foreach ($keys as $key) {
                try {
                    $result = $this->collection->sendToExact($key, 'reserve', [0]);
                    if ($result['response'] === null) {
                        continue;
                    }

                    $result['response']['id'] = $this->combineId($result['connection'], (int)$result['response']['id']);
                    return $result['response'];
                } catch (RuntimeException $e) {
                    // ignore servers not responding
                }
            }

            usleep(25 * 1000);
        } while (time() - $startTime < $timeout);

        return null;
    }

    /**
     * @param string|int $id
     */
    public function touch($id): self
    {
        [$key, $jobId] = $this->splitId($id);
        $this->collection->sendToExact($key, 'touch', [$jobId]);
        return $this;
    }

    /**
     * @param string|int $id
     */
    public function release($id, int $priority = self::DEFAULT_PRIORITY, int $delay = self::DEFAULT_DELAY): self
    {
        [$key, $jobId] = $this->splitId($id);
        $this->collection->sendToExact($key, 'release', [$jobId, $priority, $delay]);
        return $this;
    }

    /**
     * @param string|int $id
     */
    public function bury($id, int $priority = self::DEFAULT_PRIORITY): self
    {
        [$key, $jobId] = $this->splitId($id);
        $this->collection->sendToExact($key, 'bury', [$jobId, $priority]);
        return $this;
    }

    /**
     * @param string|int $id
     */
    public function delete($id): self
    {
        [$key, $jobId] = $this->splitId($id);
        $this->collection->sendToExact($key, 'delete', [$jobId]);
        return $this;
    }

    public function watch(string $tube): self
    {
        if (!isset($this->watching[$tube])) {
            $this->collection->sendToAll('watch', [$tube]);
            $this->watching[$tube] = true;
        }
        return $this;
    }

    public function ignore(string $tube): ?int
    {
        if (isset($this->watching[$tube])) {
            if (count($this->watching) === 1) {
                return null;
            }
            $this->collection->sendToAll('ignore', [$tube]);
            unset($this->watching[$tube]);
        }
        return count($this->watching);
    }

    /**
     * @param string|int $id
     */
    public function peek($id): array
    {
        [$key, $jobId] = $this->splitId($id);
        $result = $this->collection->sendToExact($key, 'peek', [$jobId]);
        $job = $result['response'];
        $job['id'] = $id;
        return $job;
    }

    public function peekReady(): ?array
    {
        return $this->peekStatus('peekReady');
    }

    public function peekDelayed(): ?array
    {
        return $this->peekStatus('peekDelayed');
    }

    public function peekBuried(): ?array
    {
        return $this->peekStatus('peekBuried');
    }

    protected function peekStatus(string $command): ?array
    {
        try {
            $result = $this->collection->sendToOne($command, []);
        } catch (RuntimeException $e) {
            return null;
        }
        if (isset($result['response']) && is_array($result['response']) && isset($result['response']['id'])) {
            $result['response']['id'] = $this->combineId($result['connection'], (int)$result['response']['id']);
        }
        return $result['response'];
    }

    public function kick(int $quantity): int
    {
        $kicked = 0;
        $onSuccess = function (array $result) use ($quantity, &$kicked): bool {
            $stats = $result['response'];
            $buriedJobs = (int)$stats['current-jobs-buried'] ?? 0;

            if ($buriedJobs === 0) {
                return true;
            }

            $kick = min($buriedJobs, $quantity - $kicked);
            $kicked += (int)$result['connection']->kick($kick);

            if ($kicked >= $quantity) {
                return false;
            }
            return true;
        };
        $this->collection->sendToAll('statsTube', [$this->using], $onSuccess);

        return $kicked;
    }

    public function stats(): ?array
    {
        $stats = [];
        $onSuccess = function (array $result) use (&$stats): bool {
            if (!is_array($result['response'])) {
                return true;
            }
            $stats = $this->statsCombine($stats, $result['response']);
            return true;
        };
        $this->collection->sendToAll('stats', [], $onSuccess);

        if (!is_array($stats) || empty($stats)) {
            return null;
        }
        return $stats;
    }

    /**
     * @param string|int $id
     */
    public function statsJob($id): array
    {
        [$key, $jobId] = $this->splitId($id);
        $result = $this->collection->sendToExact($key, 'statsJob', [$jobId]);
        $job = $result['response'];
        $job['id'] = $id;
        return $job;
    }

    public function statsTube(string $tube): ?array
    {
        $stats = [];
        $onSuccess = function (array $result) use (&$stats): bool {
            if (!is_array($result['response'])) {
                return true;
            }
            $stats = $this->statsCombine($stats, $result['response']);
            return true;
        };
        $this->collection->sendToAll('statsTube', [$tube], $onSuccess);

        if (!is_array($stats) || empty($stats)) {
            return null;
        }
        return $stats;
    }

    protected function statsCombine(array $cumulative, array $stats): array
    {
        $list = ['pid', 'version', 'hostname', 'name', 'uptime', 'binlog-current-index'];
        $maximum = ['timeouts', 'binlog-max-size', 'binlog-oldest-index'];
        foreach ($stats as $name => $value) {
            if (!array_key_exists($name, $cumulative)) {
                $cumulative[$name] = $value;
                continue;
            }

            if (in_array($name, $list, true)) {
                if ($cumulative[$name] !== $value) {
                    $cumulative[$name] .= ',' . $value;
                }
            } elseif (in_array($name, $maximum, true)) {
                if ($value > $cumulative[$name]) {
                    $cumulative[$name] = $value;
                }
            } else {
                $cumulative[$name] += $value;
            }
        }
        return $cumulative;
    }

    public function listTubes(): array
    {
        $tubes = [];
        $onSuccess = function (array $result) use (&$tubes): bool {
            if (!is_array($result['response'])) {
                return true;
            }
            $tubes = array_merge($result['response'], $tubes);
            return true;
        };
        $this->collection->sendToAll('listTubes', [], $onSuccess);
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

    public function combineId(ConnectionInterface $connection, int $id): string
    {
        if (!is_numeric($id)) {
            throw new InvalidArgumentException('Specified job id must be a number.');
        }
        return "{$connection->getName()}.{$id}";
    }

    public function splitId(string $id): array
    {
        if (strpos($id, '.') === false) {
            throw new InvalidArgumentException('Job ID is not in expected pool format.');
        }

        $position = strrpos($id, '.');
        $key = substr($id, 0, $position);
        $jobId = (int)substr($id, $position + 1);

        return [$key, $jobId];
    }
}
