<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;

/**
 * Class ListTubeUsed
 * @package Phlib\Beanstalk\Command
 */
class ListTubeUsed implements CommandInterface
{
    public function getCommand(): string
    {
        return 'list-tube-used';
    }

    public function process(SocketInterface $socket): string
    {
        $socket->write('list-tube-used');
        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'USING':
                return strtok(' ');

            default:
                throw new CommandException("List tube used failed '{$status}'");
        }
    }
}
