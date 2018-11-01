<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\ConnectionInterface;
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
    private $using = Connection::DEFAULT_TUBE;

    /**
     * @var array
     */
    private $watching = [Connection::DEFAULT_TUBE => true];

    /**
     * @var array
     */
    private $ignoring = [];

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
    public function send(string $command, ...$arguments): array
    {
        $this->intercept($command, ...$arguments);
        return $this->doSend($command, ...$arguments);
    }

    /**
     * @param string $command
     * @param array ...$arguments
     * @return array
     */
    protected function doSend(string $command, ...$arguments): array
    {
        try {
            if ($this->retryAt !== false) {
                $this->reset();
            }
            $result = $this->connection->{$command}(...$arguments);
            return ['connection' => $this->connection, 'response' => $result];
        } catch (RuntimeException $e) {
            if ($this->retryAt === false) {
                $this->delay();
            }
            throw $e;
        }
    }

    /**
     * Records certain commands to maintain a required re-initialisation state.
     * @param string $command
     * @param array ...$arguments
     */
    protected function intercept(string $command, ...$arguments): void
    {
        switch ($command) {
            case 'useTube':
                $this->useTube($arguments[0]);
                break;
            case 'watch':
                $this->watch($arguments[0]);
                break;
            case 'ignore':
                $this->ignore($arguments[0]);
                break;
        }
    }

    protected function delay(): void
    {
        $this->retryAt = time() + $this->retryDelay;
    }

    /**
     * When a connection has been lost, it could have missed important commands like use, watch and ignore.
     * These are recorded and replayed when the connection comes back.
     */
    protected function reset(): void
    {
        $this->retryAt = false;
        $this->doSend('useTube', $this->using);
        foreach (array_keys($this->watching) as $tube) {
            $this->doSend('watch', $tube);
        }
        foreach (array_keys($this->ignoring) as $tube) {
            $this->doSend('ignore', $tube);
        }
    }

    /**
     * @param string $tube
     */
    protected function useTube(string $tube): void
    {
        $this->using = $tube;
    }

    /**
     * @param string $tube
     */
    protected function watch(string $tube): void
    {
        if (array_key_exists($tube, $this->ignoring)) {
            unset($this->ignoring[$tube]);
        }
        $this->watching[$tube] = true;
    }

    /**
     * @param string $tube
     */
    protected function ignore(string $tube): void
    {
        if (array_key_exists($tube, $this->watching)) {
            unset($this->watching[$tube]);
        }
        $this->ignoring[$tube] = true;
    }
}
