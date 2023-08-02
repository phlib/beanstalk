<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\Exception as BeanstalkException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\RuntimeException;

/**
 * @package Phlib\Beanstalk
 */
class Collection implements CollectionInterface
{
    private array $connections;

    private array $options;

    /**
     * @param ConnectionInterface[] $connections
     */
    public function __construct(array $connections, array $options = [])
    {
        $formatted = [];
        foreach ($connections as $connection) {
            if (!$connection instanceof ConnectionInterface) {
                throw new InvalidArgumentException('Invalid connection specified for pool collection.');
            }
            $key = $connection->getName();
            $formatted[$key] = [
                'connection' => $connection,
                'retry_at' => false,
            ];
        }
        $this->connections = $formatted;

        $this->options = $options + [
            'retry_delay' => 600,
        ];
    }

    public function getIterator(): \Traversable
    {
        $connections = [];
        foreach (array_keys($this->connections) as $key) {
            try {
                $connections[] = $this->getConnection($key);
            } catch (RuntimeException $e) {
                // ignore bad connections
            }
        }
        return new \ArrayIterator($connections);
    }

    public function getConnection(string $key): ConnectionInterface
    {
        if (!array_key_exists($key, $this->connections)) {
            throw new NotFoundException("Specified key '{$key}' is not in the pool.");
        }
        $retryAt = $this->connections[$key]['retry_at'];
        if ($retryAt !== false && $retryAt > time()) {
            throw new RuntimeException('Connection recently failed.');
        } elseif ($retryAt !== false && $retryAt <= time()) {
            $this->connections[$key]['retry_at'] = false;
        }
        return $this->connections[$key]['connection'];
    }

    public function getAvailableKeys(): array
    {
        $keys = [];
        foreach (array_keys($this->connections) as $key) {
            try {
                $this->getConnection($key);
                $keys[] = $key;
            } catch (RuntimeException $e) {
                // ignore bad connections
            }
        }
        return $keys;
    }

    /**
     * @return mixed
     */
    public function sendToOne(string $command, array $arguments = [])
    {
        $e = null;

        $keysAvailable = array_keys($this->connections);
        shuffle($keysAvailable);
        foreach ($keysAvailable as $key) {
            try {
                $result = $this->sendToExact($key, $command, $arguments);
                if ($result['response'] !== null) {
                    return $result;
                }
            } catch (RuntimeException $e) {
                // ignore
            }
        }

        $message = "Failed to send command '{$command}' to one of the connections.";
        if ($e instanceof \Exception) {
            $final = new RuntimeException($message, 0, $e);
        } else {
            $final = new RuntimeException($message);
        }
        throw $final;
    }

    /**
     * @return mixed
     */
    public function sendToExact(string $key, string $command, array $arguments = [])
    {
        try {
            $connection = $this->getConnection($key);

            // Use switch instead of `->{$command}` to allow static analysis
            switch ($command) {
                case 'useTube':
                    $result = $connection->useTube(...$arguments);
                    break;
                case 'put':
                    $result = $connection->put(...$arguments);
                    break;
                case 'reserve':
                    $result = $connection->reserve(...$arguments);
                    break;
                case 'touch':
                    $result = $connection->touch(...$arguments);
                    break;
                case 'release':
                    $result = $connection->release(...$arguments);
                    break;
                case 'bury':
                    $result = $connection->bury(...$arguments);
                    break;
                case 'delete':
                    $result = $connection->delete(...$arguments);
                    break;
                case 'watch':
                    $result = $connection->watch(...$arguments);
                    break;
                case 'ignore':
                    $result = $connection->ignore(...$arguments);
                    break;
                case 'peek':
                    $result = $connection->peek(...$arguments);
                    break;
                case 'statsJob':
                    $result = $connection->statsJob(...$arguments);
                    break;
                case 'peekReady':
                    $result = $connection->peekReady();
                    break;
                case 'peekDelayed':
                    $result = $connection->peekDelayed();
                    break;
                case 'peekBuried':
                    $result = $connection->peekBuried();
                    break;
                case 'kick':
                    $result = $connection->kick(...$arguments);
                    break;
                case 'statsTube':
                    $result = $connection->statsTube(...$arguments);
                    break;
                case 'stats':
                    $result = $connection->stats();
                    break;
                case 'listTubes':
                    $result = $connection->listTubes();
                    break;
                case 'listTubeUsed':
                    $result = $connection->listTubeUsed();
                    break;
                case 'listTubesWatched':
                    $result = $connection->listTubesWatched();
                    break;
                default:
                    throw new InvalidArgumentException("Specified command '{$command}' is not allowed.");
            }
            $this->connections[$key]['retry_at'] = false;

            return [
                'connection' => $connection,
                'response' => $result,
            ];
        } catch (RuntimeException $e) {
            if ($this->connections[$key]['retry_at'] === false) {
                $retryDelay = $this->options['retry_delay'];
                $this->connections[$key]['retry_at'] = time() + $retryDelay; // 10 minutes
            }
            throw $e;
        }
    }

    /**
     * @param callable|null $success {
     *     @param array $result
     *     @return bool continue iteration to other connections
     * }
     * @param callable|null $failure {
     *     @return bool continue iteration to other connections
     * }
     */
    public function sendToAll(
        string $command,
        array $arguments = [],
        callable $success = null,
        callable $failure = null
    ): void {
        foreach (array_keys($this->connections) as $key) {
            try {
                $result = $this->sendToExact($key, $command, $arguments);
            } catch (\Exception $e) {
                if (!($e instanceof BeanstalkException)) {
                    throw $e;
                }
                // ignore
                $result = [
                    'response' => null,
                ];
            }

            $continue = true;
            if ($result['response'] === null && $failure !== null) {
                $continue = call_user_func($failure);
            } elseif ($result['response'] !== null && $success !== null) {
                $continue = call_user_func($success, $result);
            }

            if ($continue === false) {
                return;
            }
        }
    }
}
