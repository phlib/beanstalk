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
 * Class Factory
 * @package Phlib\Beanstalk
 */
class Factory
{
    public function create(string $host, int $port = Socket::DEFAULT_PORT, array $options = []): Connection
    {
        return new Connection(new Socket($host, $port, $options));
    }

    public function createFromArray(array $config): ConnectionInterface
    {
        if (array_key_exists('host', $config)) {
            $config = [
                'server' => $config,
            ];
        }

        if (array_key_exists('server', $config)) {
            $server = $this->normalizeArgs($config['server']);
            $connection = $this->create($server['host'], $server['port'], $server['options']);
        } elseif (array_key_exists('servers', $config)) {
            if (!isset($config['strategyClass'])) {
                $config['strategyClass'] = RoundRobinStrategy::class;
            }
            $servers = $this->createConnections($config['servers']);
            $strategy = $this->createStrategy($config['strategyClass']);
            $connection = new Pool(new Collection($servers, $strategy));
        } else {
            throw new InvalidArgumentException('Missing required server(s) configuration');
        }

        return $connection;
    }

    /**
     * @return Connection[]
     */
    public function createConnections(array $servers): array
    {
        $connections = [];
        foreach ($servers as $server) {
            if (array_key_exists('enabled', $server) && $server['enabled'] === false) {
                continue;
            }
            $server = $this->normalizeArgs($server);
            $connection = $this->create($server['host'], $server['port'], $server['options']);
            $connections[] = $connection;
        }
        return $connections;
    }

    public function createStrategy(string $class): SelectionStrategyInterface
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
