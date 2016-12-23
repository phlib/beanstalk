<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\RuntimeException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @package Phlib\Beanstalk
 * @internal This class is not part of the backward-compatibility promise.
 */
class ManagedConnection implements ConnectionInterface
{
    private ConnectionInterface $connection;

    private LoggerInterface $logger;

    private int $retryDelay;

    private int $retryAt;

    private bool $hasFailed = false;

    private string $using = Connection::DEFAULT_TUBE;

    private array $watching = [Connection::DEFAULT_TUBE => true];

    private array $ignoring = [];

    public function __construct(
        ConnectionInterface $connection,
        int $retryDelay = 600,
        ?LoggerInterface $logger = null
    ) {
        $this->connection = $connection;
        $this->retryDelay = $retryDelay;

        if (!isset($logger)) {
            $logger = new NullLogger();
        }
        $this->logger = $logger;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getName(): string
    {
        return $this->connection->getName();
    }

    public function disconnect(): bool
    {
        return $this->connection->disconnect();
    }

    public function useTube(string $tube): void
    {
        $this->using = $tube;

        $this->tryCommand(
            fn() => $this->connection->useTube($tube)
        );
    }

    public function put(
        string $data,
        int $priority = self::DEFAULT_PRIORITY,
        int $delay = self::DEFAULT_DELAY,
        int $ttr = self::DEFAULT_TTR
    ): int {
        return $this->tryCommand(fn() => $this->connection->put($data, $priority, $delay, $ttr));
    }

    public function reserve(?int $timeout = null): array
    {
        return $this->tryCommand(
            fn() => $this->connection->reserve($timeout)
        );
    }

    public function touch($id): void
    {
        $this->tryCommand(
            fn() => $this->connection->touch($id)
        );
    }

    public function release($id, int $priority = self::DEFAULT_PRIORITY, int $delay = self::DEFAULT_DELAY): void
    {
        $this->tryCommand(
            fn() => $this->connection->release($id, $priority, $delay)
        );
    }

    public function bury($id, int $priority = self::DEFAULT_PRIORITY): void
    {
        $this->tryCommand(
            fn() => $this->connection->bury($id, $priority)
        );
    }

    public function delete($id): void
    {
        $this->tryCommand(
            fn() => $this->connection->delete($id)
        );
    }

    public function watch(string $tube): int
    {
        unset($this->ignoring[$tube]);
        $this->watching[$tube] = true;

        return $this->tryCommand(
            fn() => $this->connection->watch($tube)
        );
    }

    public function ignore(string $tube): int
    {
        unset($this->watching[$tube]);
        $this->ignoring[$tube] = true;

        return $this->tryCommand(
            fn() => $this->connection->ignore($tube)
        );
    }

    public function peek($id): array
    {
        return $this->tryCommand(
            fn() => $this->connection->peek($id)
        );
    }

    public function peekReady(): array
    {
        return $this->tryCommand(
            fn() => $this->connection->peekReady()
        );
    }

    public function peekDelayed(): array
    {
        return $this->tryCommand(
            fn() => $this->connection->peekDelayed()
        );
    }

    public function peekBuried(): array
    {
        return $this->tryCommand(
            fn() => $this->connection->peekBuried()
        );
    }

    public function kick(int $quantity): int
    {
        return $this->tryCommand(
            fn() => $this->connection->kick($quantity)
        );
    }

    public function statsJob($id): array
    {
        return $this->tryCommand(
            fn() => $this->connection->statsJob($id)
        );
    }

    public function statsTube(string $tube): array
    {
        return $this->tryCommand(
            fn() => $this->connection->statsTube($tube)
        );
    }

    public function stats(): array
    {
        return $this->tryCommand(
            fn() => $this->connection->stats()
        );
    }

    public function listTubes(): array
    {
        return $this->tryCommand(
            fn() => $this->connection->listTubes()
        );
    }

    public function listTubeUsed(): string
    {
        return $this->tryCommand(
            fn() => $this->connection->listTubeUsed()
        );
    }

    public function listTubesWatched(): array
    {
        return $this->tryCommand(
            fn() => $this->connection->listTubesWatched()
        );
    }

    /**
     * @return array|int|string
     */
    private function tryCommand(\Closure $commandFn)
    {
        try {
            if ($this->hasFailed) {
                // Trying to reconnect; need to replay tube selections
                $this->replayTubes();
            }

            $result = $commandFn();
            $this->reset();

            return $result;
        } catch (RuntimeException $e) {
            if (!isset($this->retryAt)) {
                $this->delay();
            }
            throw $e;
        }
    }

    public function isAvailable(): bool
    {
        return !isset($this->retryAt) || $this->retryAt <= time();
    }

    private function replayTubes(): void
    {
        $this->connection->useTube($this->using);

        foreach (array_keys($this->watching) as $watchTube) {
            $this->connection->watch($watchTube);
        }

        foreach (array_keys($this->ignoring) as $ignoreTube) {
            $this->connection->ignore($ignoreTube);
        }
    }

    private function reset(): void
    {
        unset($this->retryAt);
        $this->hasFailed = false;
    }

    private function delay(): void
    {
        $this->hasFailed = true;
        $this->retryAt = time() + $this->retryDelay;
        $this->logger->notice(
            sprintf('Connection \'%s\' failed; delay for %ds', $this->getName(), $this->retryDelay),
            [
                'connectionName' => $this->getName(),
                'retryDelay' => $this->retryDelay,
                'retryAt' => $this->retryAt,
            ]
        );
    }
}
