<?php

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\RuntimeException;

/**
 * Class Collection
 * @package Phlib\Beanstalk\Pool
 */
class Collection implements CollectionInterface
{
    /**
     * @var array
     */
    protected $connections;

    /**
     * @var SelectionStrategyInterface
     */
    protected $strategy;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param ConnectionInterface[] $connections
     * @param SelectionStrategyInterface|null $strategy
     * @param array $options
     * @throws InvalidArgumentException
     */
    public function __construct(array $connections, SelectionStrategyInterface $strategy = null, array $options = [])
    {
        $formatted = [];
        foreach ($connections as $connection) {
            if (!$connection instanceof Connection) {
                throw new InvalidArgumentException('Invalid connection specified for pool collection.');
            }
            $key = $connection->getUniqueIdentifier();
            $formatted[$key] = [
                'connection' => $connection,
                'retry_at'   => false
            ];
        }
        $this->connections = $formatted;

        if (is_null($strategy)) {
            $strategy = new RoundRobinStrategy();
        }
        $this->strategy = $strategy;
        $this->options = $options;
    }

    /**
     * @inheritdoc
     */
    public function getConnection($key)
    {
        if (!array_key_exists($key, $this->connections)) {
            throw new NotFoundException("Specified key '$key' is not in the pool.");
        }
        $retryAt = $this->connections[$key]['retry_at'];
        if ($retryAt !== false && $retryAt > time()) {
            throw new RuntimeException('Connection recently failed.');
        }
        return $this->connections[$key]['connection'];
    }

    /**
     * @inheritdoc
     */
    public function sendToExact($key, $command, array $arguments = [])
    {
        try {
            $connection = $this->getConnection($key);
            $result     = call_user_func_array([$connection, $command], $arguments);
            $this->connections[$key]['retry_at'] = false;

            if (is_array($result) and array_key_exists('id', $result)) {
                $result['id'] = $this->combineId($connection, $result['id']);
            }

            return ['connection' => $connection, 'response' => $result];
        } catch (RuntimeException $e) {
            if ($this->connections[$key]['retry_at'] === false) {
                $this->connections[$key]['retry_at'] = time() + 600; // 10 minutes
            }
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function sendToAll($command, array $arguments = [])
    {
        $results = [];
        $keys = array_keys($this->connections);
        foreach ($keys as $key) {
            try {
                $results[$key] = $this->sendToExact($key, $command, $arguments);
            } catch (\Exception $e) {
                // ignore
            }
        }
        return $results;
    }

    /**
     * @inheritdoc
     */
    public function sendToOne($command, array $arguments = [])
    {
        $e = null;
        foreach ($this->randomKeys() as $key) {
            try {
                $result = $this->sendToExact($key, $command, $arguments);
                if ($result['response'] !== false) {
                    return $result;
                }
            } catch (RuntimeException $e) {
                // ignore
            }
        }

        if ($e instanceof \Exception) {
            throw $e;
        }
        return ['connection' => null, 'response' => false];
    }
}
