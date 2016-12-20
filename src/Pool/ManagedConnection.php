<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\RuntimeException;

class ManagedConnection
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var int
     */
    private $retryDelay;

    /**
     * @var bool|int
     */
    private $retryAt = false;

    /**
     * @var string
     */
    private $tube = Connection::DEFAULT_TUBE;

    /**
     * @var array
     */
    private $watching = [Connection::DEFAULT_TUBE];

    private $ignore = [];

    /**
     * @param ConnectionInterface $connection
     * @param int $retryDelay
     */
    public function __construct(ConnectionInterface $connection, $retryDelay = 600)
    {
        $this->connection = $connection;
        $this->retryDelay = $retryDelay;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->retryAt === false || $this->retryAt <= time();
    }

    /**
     * @param string $command
     * @param array $arguments
     * @return array
     */
    public function send(string $command, ...$arguments)
    {
        try {
            $result = $this->connection->{$command}(...$arguments);
            if ($this->retryAt !== false) {
                $this->reset();
            }
            return ['connection' => $this->connection, 'response' => $result];
        } catch (RuntimeException $e) {
            if ($this->retryAt === false) {
                $this->delay();
            }
            throw $e;
        }
    }

    protected function reset()
    {
        $this->retryAt = false;
        // in case during the missing connection we're no longer using or watching the right tubes.
        // $this->connection->useTube($this->tube);
        // foreach(array_keys($this->watching) as $watch) {$this->connection->watch(}
        // ignore
    }

    protected function delay()
    {
        $this->retryAt = time() + $this->retryDelay;
    }
}
