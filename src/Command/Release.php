<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\ValidateTrait;

/**
 * Class Release
 * @package Phlib\Beanstalk\Command
 */
class Release implements CommandInterface
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
     * @var integer
     */
    protected $delay;

    /**
     * @param string  $id
     * @param integer $priority
     * @param integer $delay
     */
    public function __construct($id, $priority, $delay)
    {
        $this->validatePriority($priority);

        $this->id       = $id;
        $this->priority = $priority;
        $this->delay    = $delay;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return sprintf('release %d %d %d', $this->id, $this->priority, $this->delay);
    }

    /**
     * @param SocketInterface $socket
     * @return $this
     * @throws NotFoundException
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
    {
        $socket->write($this->getCommand());

        $response = $socket->read();
        switch ($response) {
            case 'RELEASED':
            case 'BURIED':
                return $this;

            case 'NOT_FOUND':
                throw new NotFoundException("Job id '$this->id' could not be found.");

            default:
                throw new CommandException("Release '$this->id' failed '$response'");
        }
    }
}
