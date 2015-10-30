<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;

/**
 * Class ListTubeUsed
 * @package Phlib\Beanstalk\Command
 */
class ListTubeUsed implements CommandInterface
{
    use ToStringTrait;

    /**
     * @return string
     */
    public function getCommand()
    {
        return 'list-tube-used';
    }

    /**
     * @param SocketInterface $socket
     * @return string
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
    {
        $socket->write('list-tube-used');
        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'USING':
                return strtok(' ');

            default:
                throw new CommandException("List tube used failed '$status'");
        }
    }
}
