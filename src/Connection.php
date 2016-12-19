<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\NotFoundException;

class Connection implements ConnectionInterface
{
    const DEFAULT_TUBE = 'default';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var SocketInterface
     */
    protected $socket;

    /**
     * @var string
     */
    protected $using = self::DEFAULT_TUBE;

    /**
     * @var array
     */
    protected $watching = [self::DEFAULT_TUBE => true];

    /**
     * @param string $host
     * @param int $port
     * @param array $options
     */
    public function __construct($host, $port = Socket::DEFAULT_PORT, array $options = [])
    {
        $socket = new Socket($host, $port, $options);
        $this->name = $socket->getUniqueIdentifier();
        $this->setSocket($socket);
    }

    /**
     * @return Socket
     */
    public function getSocket(): Socket
    {
        return $this->socket;
    }

    /**
     * @param Socket $socket
     * @return $this
     */
    public function setSocket(Socket $socket)
    {
        $this->socket = $socket;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disconnect(): bool
    {
        return $this->socket->disconnect();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $value
     * @return Connection
     */
    public function setName(string $value): Connection
    {
        $this->name = $value;
        return $this;
    }

    /**
     * @param string $tube
     * @return ConnectionInterface
     * @throws Exception\CommandException
     */
    public function useTube(string $tube): ConnectionInterface
    {
        (new Command\UseTube($tube))
            ->process($this->getSocket());
        $this->using = $tube;
        return $this;
    }

    /**
     * @param string $data
     * @param int $priority
     * @param int $delay
     * @param int $ttr
     * @return int
     * @throws Exception\CommandException
     */
    public function put(
        string $data,
        int $priority = ConnectionInterface::DEFAULT_PRIORITY,
        int $delay = ConnectionInterface::DEFAULT_DELAY,
        int $ttr = ConnectionInterface::DEFAULT_TTR
    ) {
        $data = (string)$data;
        return (new Command\Put($data, $priority, $delay, $ttr))
            ->process($this->getSocket());
    }

    /**
     * @param int $timeout
     * @return array|false
     * @throws Exception\CommandException
     */
    public function reserve(int $timeout = null)
    {
        $jobData = (new Command\Reserve($timeout))
            ->process($this->getSocket());
        return $jobData;
    }

    /**
     * @param int $id
     * @return ConnectionInterface
     * @throws Exception\NotFoundException
     * @throws Exception\CommandException
     */
    public function delete($id): ConnectionInterface
    {
        (new Command\Delete($id))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param int $id
     * @param int $priority
     * @param int $delay
     * @return ConnectionInterface
     * @throws Exception\NotFoundException
     * @throws Exception\CommandException
     */
    public function release(
        $id,
        int $priority = ConnectionInterface::DEFAULT_PRIORITY,
        int $delay = ConnectionInterface::DEFAULT_DELAY
    ): ConnectionInterface {
        (new Command\Release($id, $priority, $delay))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param int $id
     * @param int $priority
     * @return ConnectionInterface
     * @throws Exception\NotFoundException
     * @throws Exception\CommandException
     */
    public function bury($id, int $priority = ConnectionInterface::DEFAULT_PRIORITY): ConnectionInterface
    {
        (new Command\Bury($id, $priority))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param int $id
     * @return ConnectionInterface
     * @throws Exception\NotFoundException
     * @throws Exception\CommandException
     */
    public function touch($id): ConnectionInterface
    {
        (new Command\Touch($id))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param string $tube
     * @return ConnectionInterface
     * @throws Exception\CommandException
     */
    public function watch(string $tube): ConnectionInterface
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
     * @param string $tube
     * @return int|false
     * @throws Exception\CommandException
     */
    public function ignore(string $tube)
    {
        if (isset($this->watching[$tube])) {
            if (count($this->watching) == 1) {
                return false;
            }

            (new Command\Ignore($tube))
                ->process($this->getSocket());
            unset($this->watching[$tube]);
        }

        return count($this->watching);
    }

    /**
     * @param int $id
     * @return array
     */
    public function peek($id)
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
        return $this->peekStatus(Command\Peek::READY);
    }

    /**
     * @return array|false
     */
    public function peekDelayed()
    {
        return $this->peekStatus(Command\Peek::DELAYED);
    }

    /**
     * @return array|false
     */
    public function peekBuried()
    {
        return $this->peekStatus(Command\Peek::BURIED);
    }

    /**
     * @param string $status
     * @return array|false
     */
    protected function peekStatus(string $status)
    {
        try {
            $jobData = (new Command\Peek($status))
                ->process($this->getSocket());
            return $jobData;
        } catch (NotFoundException $e) {
            return false;
        }
    }

    /**
     * @return array
     */
    public function stats(): array
    {
        return (new Command\Stats())
            ->process($this->getSocket());
    }

    /**
     * @param int $id
     * @return array
     */
    public function statsJob($id): array
    {
        return (new Command\StatsJob($id))
            ->process($this->getSocket());
    }

    /**
     * @param string $tube
     * @return array
     */
    public function statsTube(string $tube): array
    {
        return (new Command\StatsTube($tube))
            ->process($this->getSocket());
    }

    /**
     * @param int $quantity
     * @return int
     */
    public function kick(int $quantity): int
    {
        return (new Command\Kick($quantity))
            ->process($this->getSocket());
    }

    /**
     * @return array
     */
    public function listTubes(): array
    {
        return (new Command\ListTubes())
            ->process($this->getSocket());
    }

    /**
     * @return string
     */
    public function listTubeUsed(): string
    {
        return $this->using;
    }

    /**
     * @return array
     */
    public function listTubesWatched(): array
    {
        return array_keys($this->watching);
    }
}
