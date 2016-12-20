<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\Socket;

class Factory
{
    /**
     * @param array $config
     * @return ConnectionInterface
     */
    public static function createFromArray(array $config): ConnectionInterface
    {
        if (array_key_exists('host', $config)) {
            $server     = static::normalizeArgs($config);
            $connection = new Connection($server['host'], $server['port'], $server['options']);
        } else {
            $servers    = static::createConnections($config);
            $connection = new Pool($servers);
        }

        return $connection;
    }

    /**
     * @param array $servers
     * @return Connection[]
     */
    public static function createConnections(array $servers): array
    {
        $connections = [];
        foreach ($servers as $index => $server) {
            if (array_key_exists('enabled', $server) && $server['enabled'] == false) {
                continue;
            }
            $server     = static::normalizeArgs($server);
            $connection = new Connection($server['host'], $server['port'], $server['options']);
            if (!is_int($index)) {
                $connection->setName($index);
            }
            $connections[] = $connection;
        }
        return $connections;
    }

    /**
     * @param array $serverArgs
     * @return array
     */
    protected static function normalizeArgs(array $serverArgs): array
    {
        return $serverArgs + [
            'host' => null,
            'port' => Socket::DEFAULT_PORT,
            'options' => []
        ];
    }
}
