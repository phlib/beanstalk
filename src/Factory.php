<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Pool\Collection;
use Phlib\Beanstalk\Pool\RoundRobinStrategy;
use Phlib\Beanstalk\Pool\SelectionStrategyInterface;

/**
 * @package Phlib\Beanstalk
 */
class Factory
{
    /**
     * @deprecated 2.1.0 Static method will be changed to instance-based (with the same name) in the next major version
     */
    public static function create(string $host, int $port = Socket::DEFAULT_PORT, array $options = []): Connection
    {
        return (new static())->createBC($host, $port, $options);
    }

    public function createBC(string $host, int $port = Socket::DEFAULT_PORT, array $options = []): Connection
    {
        return new Connection(new Socket($host, $port, $options));
    }

    /**
     * @deprecated 2.1.0 Static method will be changed to instance-based (with the same name) in the next major version
     */
    public static function createFromArray(array $config): ConnectionInterface
    {
        return (new static())->createFromArrayBC($config);
    }

    public function createFromArrayBC(array $config): ConnectionInterface
    {
        if (array_key_exists('host', $config)) {
            $config = [
                'server' => $config,
            ];
        }

        if (array_key_exists('server', $config)) {
            $server = $this->normalizeArgs($config['server']);
            $connection = $this->createBC($server['host'], $server['port'], $server['options']);
        } elseif (array_key_exists('servers', $config)) {
            if (!isset($config['strategyClass'])) {
                $config['strategyClass'] = RoundRobinStrategy::class;
            }
            $servers = $this->createConnectionsBC($config['servers']);
            $strategy = $this->createStrategyBC($config['strategyClass']);
            $connection = new Pool(new Collection($servers, $strategy));
        } else {
            throw new InvalidArgumentException('Missing required server(s) configuration');
        }

        return $connection;
    }

    /**
     * @deprecated 2.1.0 Static method will be changed to instance-based (with the same name) in the next major version
     * @return Connection[]
     */
    public static function createConnections(array $servers): array
    {
        return (new static())->createConnectionsBC($servers);
    }

    /**
     * @return Connection[]
     */
    public function createConnectionsBC(array $servers): array
    {
        $connections = [];
        foreach ($servers as $server) {
            if (array_key_exists('enabled', $server) && $server['enabled'] === false) {
                continue;
            }
            $server = $this->normalizeArgs($server);
            $connection = $this->createBC($server['host'], $server['port'], $server['options']);
            $connections[] = $connection;
        }
        return $connections;
    }

    /**
     * @deprecated 2.1.0 Static method will be changed to instance-based (with the same name) in the next major version
     */
    public static function createStrategy(string $class): SelectionStrategyInterface
    {
        return (new static())->createStrategyBC($class);
    }

    public function createStrategyBC(string $class): SelectionStrategyInterface
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Specified Pool strategy class '{$class}' does not exist.");
        }
        return new $class();
    }

    private function normalizeArgs(array $serverArgs): array
    {
        return $serverArgs + [
            'host' => null,
            'port' => Socket::DEFAULT_PORT,
            'options' => [],
        ];
    }
}
