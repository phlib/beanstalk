<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Connection;

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
