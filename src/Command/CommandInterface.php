<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;

/**
 * @package Phlib\Beanstalk
 */
interface CommandInterface
{
    /**
     * @internal This method is not part of the backward-compatibility promise.
     * @return mixed
     */
    public function process(Socket $socket);
}
