<?php

namespace Phlib\Beanstalk\Pool;

/**
 * Interface CollectionInterface
 * @package Phlib\Beanstalk\Pool
 */
interface CollectionInterface
{
    /**
     * @param string $key
     * @return Connection
     */
    public function getConnection($key);

    /**
     * @param $key
     * @param $command
     * @param array $arguments
     * @return mixed
     */
    public function sendToExact($key, $command, array $arguments = []);

    /**
     * @param $command
     * @param array $arguments
     * @return mixed
     */
    public function sendToAll($command, array $arguments = []);

    /**
     * @param $command
     * @param array $arguments
     * @return mixed
     */
    public function sendToOne($command, array $arguments = []);
}
