<?php

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\Exception as BeanstalkException;
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
            if (!$connection instanceof ConnectionInterface) {
                throw new InvalidArgumentException('Invalid connection specified for pool collection.');
            }
            $key = $connection->getName();
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
        $this->options = $options + ['retry_delay' => 600];
    }

    /**
     * @return SelectionStrategyInterface
     */
    public function getSelectionStrategy()
    {
        return $this->strategy;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $connections = [];
        foreach ($this->connections as $meta) {
            $connections[] = $meta['connection'];
        }
        return new \ArrayIterator($connections);
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
        } elseif ($retryAt !== false && $retryAt <= time()) {
            $this->connections[$key]['retry_at'] = false;
        }
        return $this->connections[$key]['connection'];
    }

    /**
     * @inheritdoc
     */
    public function sendToOne($command, array $arguments = [])
    {
        $e = null;

        $keysAvailable = array_keys($this->connections);
        $keysUsed      = [];
        $keysExhausted = false;
        while (!$keysExhausted && ($key = $this->strategy->pickOne($keysAvailable)) !== false) {
            try {
                $result = $this->sendToExact($key, $command, $arguments);
                if ($result['response'] !== false) {
                    return $result;
                }
            } catch (RuntimeException $e) {
                // ignore
            }

            $keysUsed[$key] = true;
            if (count($keysUsed) == count($keysAvailable)) {
                $keysExhausted = true;
            }
        }

        if ($e instanceof \Exception) {
            throw $e;
        }
        return ['connection' => null, 'response' => false];
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

            return ['connection' => $connection, 'response' => $result];
        } catch (RuntimeException $e) {
            if ($this->connections[$key]['retry_at'] === false) {
                $retryDelay = $this->options['retry_delay'];
                $this->connections[$key]['retry_at'] = time() + $retryDelay; // 10 minutes
            }
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function sendToAll($command, array $arguments = [], callable $success = null, callable $failure = null)
    {
        foreach (array_keys($this->connections) as $key) {
            try {
                $result = $this->sendToExact($key, $command, $arguments);
            } catch (\Exception $e) {
                if (!($e instanceof BeanstalkException)) {
                    throw $e;
                }
                // ignore
                $result = ['response' => false];
            }

            $continue = true;
            if ($result['response'] === false && !is_null($failure)) {
                $continue = call_user_func($failure);
            } elseif ($result['response'] !== false && !is_null($success)) {
                $continue = call_user_func($success, $result);
            }

            if ($continue === false) {
                return;
            }
        }
    }
}
