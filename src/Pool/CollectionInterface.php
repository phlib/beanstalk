<?php

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Connection\ConnectionInterface;

/**
 * Interface CollectionInterface
 * @package Phlib\Beanstalk\Pool
 */
interface CollectionInterface extends \IteratorAggregate
{
    /**
     * @return array
     */
    public function getAvailableKeys();

    /**
     * @param string $key
     * @return ConnectionInterface
     */
    public function getConnection($key);

    /**
     * @param $key
     * @param $command
     * @return mixed
     */
    public function sendToExact($key, $command, array $arguments = []);

    /**
     * @param $command
     * @return mixed
     */
    public function sendToOne($command, array $arguments = []);

    /**
     * @param $command
     * @param callable|null $success {
     *     @param array $result
     *     @return bool continue iteration to other connections
     * }
     * @param callable|null $failure {
     *     @return bool continue iteration to other connections
     * }
     * @return mixed
     */
    public function sendToAll($command, array $arguments = [], callable $success = null, callable $failure = null);
}
