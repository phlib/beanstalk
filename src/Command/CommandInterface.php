<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;

interface CommandInterface
{
    /**
     * @param SocketInterface $socket
     * @return mixed
     */
    public function process(SocketInterface $socket);
}
