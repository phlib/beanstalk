<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\ValidateTrait;

/**
 * Class Watch
 * @package Phlib\Beanstalk\Command
 */
class Watch implements CommandInterface
{
    use ValidateTrait;
    use ToStringTrait;

    /**
     * @var string
     */
    protected $tube;

    /**
     * @param string $tube
     */
    public function __construct($tube)
    {
        $this->validateTubeName($tube);
        $this->tube = $tube;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return sprintf('watch %s', $this->tube);
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
            case 'WATCHING':
                return (int)strtok(' ');

            default:
                throw new CommandException("Watch tube '$this->tube' failed '$status'");
        }
    }
}
