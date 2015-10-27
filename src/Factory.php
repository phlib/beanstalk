<?php

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Socket;

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
     * @return BeanstalkInterface
     */
    public function create($host, $port = Socket::DEFAULT_PORT, array $options = [])
    {
        return new Beanstalk(new Socket($host, $port, $options));
    }

    /**
     * @param array $config
     * @return BeanstalkInterface
     */
    public function createFromArray(array $config)
    {
        $jobPackager = null;
        if (array_key_exists('packager', $config)) {
            $packagerClass = $config['packager'];
            if (in_array($packagerClass, ['Php', 'Json', 'Raw'])) {
                $packagerClass = "\\Phlib\\Beanstalk\\JobPackager\\{$packagerClass}";
            }
            $jobPackager = new $packagerClass;
        }
        
        if (array_key_exists('host', $config)) {
            $config = ['server' => $config];
        }

        if (array_key_exists('server', $config)) {
            $server     = $this->normalizeArgs($config['server']);
            $connection = $this->create($server['host'], $server['port'], $server['options']);
            $connection->setJobPackager($jobPackager);
        } elseif (array_key_exists('servers', $config)) {
            $connection = new BeanstalkPool($this->createConnections($config['servers'], $jobPackager));
        } else {
            throw new InvalidArgumentException('Missing required server(s) configuration');
        }

        return $connection;
    }

    /**
     * @param array                              $servers
     * @param JobPackager\PackagerInterface|null $jobPackager
     * @return Socket[]
     */
    public function createConnections(array $servers, JobPackager\PackagerInterface $jobPackager = null)
    {
        $connections = [];
        foreach ($servers as $server) {
            if (array_key_exists('enabled', $server) && $server['enabled'] == false) {
                continue;
            }
            $server = $this->normalizeArgs($server);
            $connection = $this->create($server['host'], $server['port'], $server['options']);
            $connection->setJobPackager($jobPackager);
            $connections[] = $connection;
        }
        return $connections;
    }

    /**
     * @param array $serverArgs
     * @return array
     */
    protected function normalizeArgs(array $serverArgs)
    {
        return $serverArgs + [
            'host' => null,
            'port' => Socket::DEFAULT_PORT,
            'options' => []
        ];
    }
}
