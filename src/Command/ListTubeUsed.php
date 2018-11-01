<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;

/**
 * @package Phlib\Beanstalk
 */
class ListTubeUsed implements CommandInterface
{
    private function getCommand(): string
    {
        return 'list-tube-used';
    }

    public function process(SocketInterface $socket): string
    {
        $socket->write('list-tube-used');
        $status = strtok($socket->read(), ' ');

        if ($status !== 'USING') {
            throw new CommandException("List tube used failed '{$status}'");
        }

        return strtok(' ');
    }
}
