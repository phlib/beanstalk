<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;

/**
 * @package Phlib\Beanstalk
 */
interface CommandInterface
{
    /**
     * @internal This method is not part of the backward-compatibility promise.
     * @return mixed
     */
    public function process(SocketInterface $socket);
}
