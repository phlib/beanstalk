<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;

/**
 * Interface CommandInterface
 * @package Phlib\Beanstalk\Command
 */
interface CommandInterface
{
    /**
     * @internal This method is not part of the backward-compatibility promise.
     * @return mixed
     */
    public function process(SocketInterface $socket);
}
