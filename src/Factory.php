<?php

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Connection\Socket;

/**
 * Class Factory
 * @package Phlib\Beanstalk
 */
class Factory
{
    /**
     * @param string $host
     * @param integer $port
     * @param array $options
     * @return ConnectionInterface
     */
    public static function create($host, $port = Socket::DEFAULT_PORT, array $options = [])
    {
        return new Connection(new Socket($host, $port, $options));
    }

    /**
     * @param array $config
     * @return ConnectionInterface
     */
    public static function createFromArray(array $config)
    {
        if (array_key_exists('host', $config)) {
            $config = ['server' => $config];
        }

        if (array_key_exists('server', $config)) {
            $server     = static::normalizeArgs($config['server']);
            $connection = static::create($server['host'], $server['port'], $server['options']);
        } elseif (array_key_exists('servers', $config)) {
            $connection = new Pool(static::createConnections($config['servers']));
        } else {
            throw new InvalidArgumentException('Missing required server(s) configuration');
        }

        return $connection;
    }

    /**
     * @param array $servers
     * @return Socket[]
     */
    public static function createConnections(array $servers)
    {
        $connections = [];
        foreach ($servers as $server) {
            if (array_key_exists('enabled', $server) && $server['enabled'] == false) {
                continue;
            }
            $server = static::normalizeArgs($server);
            $connection = static::create($server['host'], $server['port'], $server['options']);
            $connections[] = $connection;
        }
        return new Pool\Collection($connections);
    }

    /**
     * @param array $serverArgs
     * @return array
     */
    protected static function normalizeArgs(array $serverArgs)
    {
        return $serverArgs + [
            'host' => null,
            'port' => Socket::DEFAULT_PORT,
            'options' => []
        ];
    }
}
