<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * @package Phlib\Beanstalk
 */
class Connection implements ConnectionInterface
{
    public const DEFAULT_TUBE = 'default';

    private string $name;

    private SocketInterface $socket;

    private string $using = self::DEFAULT_TUBE;

    private array $watching = [
        self::DEFAULT_TUBE => true,
    ];

    public function __construct(SocketInterface $socket)
    {
        $this->setSocket($socket);
        $this->name = $socket->getUniqueIdentifier();
    }

    private function getSocket(): SocketInterface
    {
        return $this->socket;
    }

    private function setSocket(SocketInterface $socket): self
    {
        $this->socket = $socket;
        return $this;
    }

    public function disconnect(): bool
    {
        return $this->socket->disconnect();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function useTube(string $tube): self
    {
        (new Command\UseTube($tube))
            ->process($this->getSocket());
        $this->using = $tube;
        return $this;
    }

    public function put(
        string $data,
        int $priority = ConnectionInterface::DEFAULT_PRIORITY,
        int $delay = ConnectionInterface::DEFAULT_DELAY,
        int $ttr = ConnectionInterface::DEFAULT_TTR
    ): int {
        $data = (string)$data;
        return (new Command\Put($data, $priority, $delay, $ttr))
            ->process($this->getSocket());
    }

    public function reserve(?int $timeout = null): ?array
    {
        $jobData = (new Command\Reserve($timeout))
            ->process($this->getSocket());
        return $jobData;
    }

    /**
     * @param string|int $id
     */
    public function delete($id): self
    {
        $id = $this->filterJobId($id);
        (new Command\Delete($id))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param string|int $id
     */
    public function release(
        $id,
        int $priority = ConnectionInterface::DEFAULT_PRIORITY,
        int $delay = ConnectionInterface::DEFAULT_DELAY
    ): self {
        $id = $this->filterJobId($id);
        (new Command\Release($id, $priority, $delay))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param string|int $id
     */
    public function bury($id, int $priority = ConnectionInterface::DEFAULT_PRIORITY): self
    {
        $id = $this->filterJobId($id);
        (new Command\Bury($id, $priority))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param string|int $id
     */
    public function touch($id): self
    {
        $id = $this->filterJobId($id);
        (new Command\Touch($id))
            ->process($this->getSocket());
        return $this;
    }

    public function watch(string $tube): self
    {
        if (isset($this->watching[$tube])) {
            return $this;
        }

        (new Command\Watch($tube))
            ->process($this->getSocket());
        $this->watching[$tube] = true;

        return $this;
    }

    public function ignore(string $tube): ?int
    {
        if (isset($this->watching[$tube])) {
            if (count($this->watching) === 1) {
                return null;
            }

            (new Command\Ignore($tube))
                ->process($this->getSocket());
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
        $jobData = (new Command\Peek($id))
            ->process($this->getSocket());
        return $jobData;
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
            $jobData = (new Command\PeekStatus($status))
                ->process($this->getSocket());
            return $jobData;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    public function stats(): array
    {
        return (new Command\Stats())
            ->process($this->getSocket());
    }

    /**
     * @param string|int $id
     */
    public function statsJob($id): array
    {
        $id = $this->filterJobId($id);
        return (new Command\StatsJob($id))
            ->process($this->getSocket());
    }

    public function statsTube(string $tube): ?array
    {
        return (new Command\StatsTube($tube))
            ->process($this->getSocket());
    }

    public function kick(int $quantity): int
    {
        return (new Command\Kick($quantity))
            ->process($this->getSocket());
    }

    public function listTubes(): array
    {
        return (new Command\ListTubes())
            ->process($this->getSocket());
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
