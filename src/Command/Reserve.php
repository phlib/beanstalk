<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;

/**
 * Class Reserve
 * @package Phlib\Beanstalk\Command
 */
class Reserve implements CommandInterface
{
    use ToStringTrait;

    /**
     * @var integer|null
     */
    protected $timeout;

    /**
     * @param integer|null $timeout
     */
    public function __construct($timeout = null)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        if (is_null($this->timeout)) {
            return 'reserve';
        } else {
            return sprintf('reserve-with-timeout %d', $this->timeout);
        }
    }

    /**
     * @param SocketInterface $socket
     * @return array
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
    {
        $socket->write($this->getCommand());
        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'RESERVED':
                $id     = (int)strtok(' ');
                $bytes  = (int)strtok(' ');
                $body   = substr($socket->read($bytes + 2), 0, -2);

                return ['id' => $id, 'body' => $body];

            case 'DEADLINE_SOON':
            case 'TIMED_OUT':
                return false;

            default:
                throw new CommandException("Reserve failed '$status'");
        }
    }
}
