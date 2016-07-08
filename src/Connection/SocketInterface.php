<?php

namespace Phlib\Beanstalk\Connection;

/**
 * Interface SocketInterface
 * @package Phlib\Beanstalk
 */
interface SocketInterface
{
    /**
     * @return string
     */
    public function getUniqueIdentifier();

    /**
     * @param string $data
     * @return $this
     */
    public function write($data);

    /**
     * @param integer|null $length
     * @return string
     */
    public function read($length = null);

    /**
     * @return bool
     */
    public function disconnect();
}
