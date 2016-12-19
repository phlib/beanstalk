<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;

interface CommandInterface
{
    /**
     * @return string
     */
    public function getCommand();

    /**
     * @param SocketInterface $socket
     * @return mixed
     */
    public function process(SocketInterface $socket);

    /**
     * @return string
     */
    public function __toString();
}
