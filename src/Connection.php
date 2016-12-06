<?php

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
     * @var boolean
     */
    protected $askServer = false;

    /**
     * Constructor
     *
     * @param SocketInterface $socket
     */
    public function __construct(SocketInterface $socket)
    {
        $this->setSocket($socket);
        $this->name = $socket->getUniqueIdentifier();
    }

    /**
     * @return SocketInterface
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @param SocketInterface $socket
     * @return $this
     */
    public function setSocket(SocketInterface $socket)
    {
        $this->socket = $socket;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        return $this->socket->disconnect();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * @param string $tube
     * @return $this
     * @throws Exception\CommandException
     */
    public function useTube($tube)
    {
        (new Command\UseTube($tube))
            ->process($this->getSocket());
        $this->using = $tube;
        return $this;
    }

    /**
     * @param string   $data
     * @param integer $priority
     * @param integer $delay
     * @param integer $ttr
     * @return integer
     * @throws Exception\CommandException
     */
    public function put(
        $data,
        $priority = ConnectionInterface::DEFAULT_PRIORITY,
        $delay = ConnectionInterface::DEFAULT_DELAY,
        $ttr = ConnectionInterface::DEFAULT_TTR
    ) {
        $data = (string)$data;
        return (new Command\Put($data, $priority, $delay, $ttr))
            ->process($this->getSocket());
    }

    /**
     * @param integer $timeout
     * @return array|false
     * @throws Exception\CommandException
     */
    public function reserve($timeout = null)
    {
        $jobData = (new Command\Reserve($timeout))
            ->process($this->getSocket());
        return $jobData;
    }

    /**
     * @param string|integer $id
     * @return $this
     * @throws Exception\NotFoundException
     * @throws Exception\CommandException
     */
    public function delete($id)
    {
        (new Command\Delete($id))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param string|integer $id
     * @param integer        $priority
     * @param integer        $delay
     * @return $this
     * @throws Exception\NotFoundException
     * @throws Exception\CommandException
     */
    public function release(
        $id,
        $priority = ConnectionInterface::DEFAULT_PRIORITY,
        $delay = ConnectionInterface::DEFAULT_DELAY
    ) {
        (new Command\Release($id, $priority, $delay))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param string|integer $id
     * @param integer        $priority
     * @return $this
     * @throws Exception\NotFoundException
     * @throws Exception\CommandException
     */
    public function bury($id, $priority = ConnectionInterface::DEFAULT_PRIORITY)
    {
        (new Command\Bury($id, $priority))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param string|integer $id
     * @return $this
     * @throws Exception\NotFoundException
     * @throws Exception\CommandException
     */
    public function touch($id)
    {
        (new Command\Touch($id))
            ->process($this->getSocket());
        return $this;
    }

    /**
     * @param string $tube
     * @return $this
     * @throws Exception\CommandException
     */
    public function watch($tube)
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
    public function ignore($tube)
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
     * @param string|integer $id
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
    protected function peekStatus($status)
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
    public function stats()
    {
        return (new Command\Stats())
            ->process($this->getSocket());
    }

    /**
     * @param string|integer $id
     * @return array
     */
    public function statsJob($id)
    {
        return (new Command\StatsJob($id))
            ->process($this->getSocket());
    }

    /**
     * @param string $tube
     * @return array
     */
    public function statsTube($tube)
    {
        return (new Command\StatsTube($tube))
            ->process($this->getSocket());
    }

    /**
     * @param integer $quantity
     * @return integer
     */
    public function kick($quantity)
    {
        return (new Command\Kick($quantity))
            ->process($this->getSocket());
    }

    /**
     * @return array
     */
    public function listTubes()
    {
        return (new Command\ListTubes())
            ->process($this->getSocket());
    }

    /**
     * @return array
     */
    public function listTubeUsed()
    {
        if ($this->askServer) {
            $this->using = (new Command\ListTubeUsed())
                ->process($this->getSocket());
        }
        return $this->using;
    }

    /**
     * @return array
     */
    public function listTubesWatched()
    {
        if ($this->askServer) {
            return (new Command\ListTubesWatched())
                ->process($this->getSocket());
        }
        return array_keys($this->watching);
    }
}
