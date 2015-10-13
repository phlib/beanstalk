<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\SocketInterface;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\ValidateTrait;

/**
 * Class Bury
 * @package Phlib\Beanstalk\Command
 */
class Bury implements CommandInterface
{
    use ValidateTrait;
    use ToStringTrait;

    /**
     * @var string|integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $priority;

    /**
     * @param string|integer $id
     * @param integer        $priority
     */
    public function __construct($id, $priority)
    {
        $this->validatePriority($priority);

        $this->id       = $id;
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return sprintf('bury %d %d', $this->id, $this->priority);
    }

    /**
     * @param SocketInterface $socket
     * @return string|integer
     * @throws NotFoundException
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
    {
        $socket->write($this->getCommand());

        $response = $socket->read();
        switch ($response) {
            case 'BURIED':
                return $this;

            case 'NOT_FOUND':
                throw new NotFoundException("Job id '$this->id' could not be found.");

            default:
                throw new CommandException("Bury id '$this->id' failed '$response'");
        }
    }
}
