<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * @package Phlib\Beanstalk
 */
class Connection implements ConnectionInterface
{
    public const DEFAULT_TUBE = 'default';

    private string $name;

    private Socket $socket;

    private string $using = self::DEFAULT_TUBE;

    private array $watching = [
        self::DEFAULT_TUBE => true,
    ];

    /**
     * @param \Closure|null $createSocket This parameter is not part of the BC promise. Used for unit test DI.
     */
    public function __construct(
        string $host,
        int $port = Socket::DEFAULT_PORT,
        array $options = [],
        \Closure $createSocket = null
    ) {
        if (isset($createSocket)) {
            $this->socket = $createSocket($host, $port, $options);
        } else {
            $this->socket = new Socket($host, $port, $options);
        }

        $this->name = $host . ':' . $port;
    }

    public function disconnect(): bool
    {
        return $this->socket->disconnect();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function useTube(string $tube): void
    {
        (new Command\UseTube($tube))
            ->process($this->socket);
        $this->using = $tube;
    }

    public function put(
        string $data,
        int $priority = ConnectionInterface::DEFAULT_PRIORITY,
        int $delay = ConnectionInterface::DEFAULT_DELAY,
        int $ttr = ConnectionInterface::DEFAULT_TTR
    ): int {
        return (new Command\Put($data, $priority, $delay, $ttr))
            ->process($this->socket);
    }

    public function reserve(?int $timeout = null): array
    {
        $jobData = (new Command\Reserve($timeout))
            ->process($this->socket);
        return $jobData;
    }

    /**
     * @param string|int $id
     */
    public function delete($id): void
    {
        $id = $this->filterJobId($id);
        (new Command\Delete($id))
            ->process($this->socket);
    }

    /**
     * @param string|int $id
     */
    public function release(
        $id,
        int $priority = ConnectionInterface::DEFAULT_PRIORITY,
        int $delay = ConnectionInterface::DEFAULT_DELAY
    ): void {
        $id = $this->filterJobId($id);
        (new Command\Release($id, $priority, $delay))
            ->process($this->socket);
    }

    /**
     * @param string|int $id
     */
    public function bury($id, int $priority = ConnectionInterface::DEFAULT_PRIORITY): void
    {
        $id = $this->filterJobId($id);
        (new Command\Bury($id, $priority))
            ->process($this->socket);
    }

    /**
     * @param string|int $id
     */
    public function touch($id): void
    {
        $id = $this->filterJobId($id);
        (new Command\Touch($id))
            ->process($this->socket);
    }

    public function watch(string $tube): int
    {
        if (!isset($this->watching[$tube])) {
            (new Command\Watch($tube))
                ->process($this->socket);
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

            (new Command\Ignore($tube))
                ->process($this->socket);
            unset($this->watching[$tube]);
        }

        return count($this->watching);
    }

    /**
     * @param string|int $id
     */
    public function peek($id): array
    {
        $id = $this->filterJobId($id);
        return (new Command\Peek($id))
            ->process($this->socket);
    }

    public function peekReady(): ?array
    {
        return $this->peekStatus(Command\PeekStatus::READY);
    }

    public function peekDelayed(): ?array
    {
        return $this->peekStatus(Command\PeekStatus::DELAYED);
    }

    public function peekBuried(): ?array
    {
        return $this->peekStatus(Command\PeekStatus::BURIED);
    }

    private function peekStatus(string $status): ?array
    {
        try {
            return (new Command\PeekStatus($status))
                ->process($this->socket);
        } catch (NotFoundException $e) {
            return null;
        }
    }

    public function kick(int $quantity): int
    {
        return (new Command\Kick($quantity))
            ->process($this->socket);
    }

    /**
     * @param string|int $id
     */
    public function statsJob($id): array
    {
        $id = $this->filterJobId($id);
        return (new Command\StatsJob($id))
            ->process($this->socket);
    }

    public function statsTube(string $tube): array
    {
        return (new Command\StatsTube($tube))
            ->process($this->socket);
    }

    public function stats(): array
    {
        return (new Command\Stats())
            ->process($this->socket);
    }

    public function listTubes(): array
    {
        return (new Command\ListTubes())
            ->process($this->socket);
    }

    public function listTubeUsed(): string
    {
        return $this->using;
    }

    public function listTubesWatched(): array
    {
        return array_keys($this->watching);
    }

    /**
     * @param string|int
     */
    private function filterJobId($id): int
    {
        if (is_int($id)) {
            return $id;
        }

        if (!is_string($id)) {
            throw new InvalidArgumentException('Job ID must be integer');
        }

        if ((string)(int)$id !== $id) {
            throw new InvalidArgumentException('Job ID must be integer');
        }

        return (int)$id;
    }
}
