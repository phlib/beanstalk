<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;

class Kick implements CommandInterface
{
    use ToStringTrait;

    /**
     * @var integer
     */
    protected $bound;

    /**
     * @param integer $bound
     */
    public function __construct($bound)
    {
        $this->bound = $bound;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return sprintf('kick %d', $this->bound);
    }

    /**
     * @param SocketInterface $socket
     * @return integer
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
    {
        $socket->write($this->getCommand());
        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'KICKED':
                return (int)strtok(' ');

            default:
                throw new CommandException("Kick with bound '$this->bound' failed '$status'");
        }
    }
}
