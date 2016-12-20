<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

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
            $config = $this->normalizeArgs($config);
            $connection = $this->create($config['host'], $config['port'], $config['options']);
        } else {
            $connections = $this->createConnections($config);
            $connection = new Pool($connections);
        }

        return $connection;
    }

    /**
     * @return Connection[]
     */
    public function createConnections(array $servers): array
    {
        if (empty($servers)) {
            throw new InvalidArgumentException('Missing server configurations');
        }

        $connections = [];
        foreach ($servers as $config) {
            if (array_key_exists('enabled', $config) && $config['enabled'] === false) {
                continue;
            }
            $config = $this->normalizeArgs($config);
            $connection = $this->create($config['host'], $config['port'], $config['options']);
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
