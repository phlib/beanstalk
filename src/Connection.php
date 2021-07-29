<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * Class Connection
 * @package Phlib\Beanstalk
 */
class Connection implements ConnectionInterface
{
    public const DEFAULT_TUBE = 'default';

    protected string $name;

    protected SocketInterface $socket;

    protected string $using = self::DEFAULT_TUBE;

    protected array $watching = [
        self::DEFAULT_TUBE => true,
    ];

    public function __construct(SocketInterface $socket)
    {
        $this->setSocket($socket);
        $this->name = $socket->getUniqueIdentifier();
    }

    public function getSocket(): SocketInterface
    {
        return $this->socket;
    }

    public function setSocket(SocketInterface $socket): self
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

    public function setName(string $value): self
    {
        $this->name = $value;
        return $this;
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

    /**
     * @return array|false
     */
    public function reserve(?int $timeout = null)
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
        (new Command\Release($id, $priority, $delay))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param string|int $id
     */
    public function bury($id, int $priority = ConnectionInterface::DEFAULT_PRIORITY): self
    {
        (new Command\Bury($id, $priority))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param string|int $id
     */
    public function touch($id): self
    {
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

    /**
     * @return int|false
     */
    public function ignore(string $tube)
    {
        if (isset($this->watching[$tube])) {
            if (count($this->watching) === 1) {
                return false;
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
        $jobData = (new Command\Peek($id))
            ->process($this->getSocket());
        return $jobData;
    }

    /**
     * @return array|false
     */
    public function peekReady()
    {
        return $this->peekStatus(Command\PeekStatus::READY);
    }

    /**
     * @return array|false
     */
    public function peekDelayed()
    {
        return $this->peekStatus(Command\PeekStatus::DELAYED);
    }

    /**
     * @return array|false
     */
    public function peekBuried()
    {
        return $this->peekStatus(Command\PeekStatus::BURIED);
    }

    /**
     * @return array|false
     */
    protected function peekStatus(string $status)
    {
        try {
            $jobData = (new Command\PeekStatus($status))
                ->process($this->getSocket());
            return $jobData;
        } catch (NotFoundException $e) {
            return false;
        }
    }

    /**
     * @return array|false
     */
    public function stats()
    {
        return (new Command\Stats())
            ->process($this->getSocket());
    }

    /**
     * @param string|int $id
     */
    public function statsJob($id): array
    {
        return (new Command\StatsJob($id))
            ->process($this->getSocket());
    }

    /**
     * @return array|false
     */
    public function statsTube(string $tube)
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
}
