<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Pool\Collection;

/**
 * @package Phlib\Beanstalk
 */
class Factory
{
    public function create(string $host, int $port = Socket::DEFAULT_PORT, array $options = []): Connection
    {
        return new Connection($host, $port, $options);
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
            $servers = $this->createConnections($config['servers']);
            $connection = new Pool(new Collection($servers));
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

    private function normalizeArgs(array $serverArgs): array
    {
        return $serverArgs + [
            'host' => null,
            'port' => Socket::DEFAULT_PORT,
            'options' => [],
        ];
    }
}
