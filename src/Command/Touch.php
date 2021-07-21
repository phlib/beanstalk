<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * Class Touch
 * @package Phlib\Beanstalk\Command
 */
class Touch implements CommandInterface
{
    use ToStringTrait;

    /**
     * @var string|integer
     */
    protected $id;

    /**
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return sprintf('touch %d', $this->id);
    }

    /**
     * @return $this
     * @throws NotFoundException
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
    {
        $socket->write($this->getCommand());

        $status = $socket->read();
        switch ($status) {
            case 'TOUCHED':
                return $this;

            case 'NOT_TOUCHED':
                throw new NotFoundException("Job id '{$this->id}' could not be found.");

            default:
                throw new CommandException("Touch id '{$this->id}' failed '{$status}'");
        }
    }
}
