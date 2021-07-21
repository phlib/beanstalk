<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * Class Delete
 * @package Phlib\Beanstalk\Command
 */
class Delete implements CommandInterface
{
    use ToStringTrait;

    /**
     * @var string|integer
     */
    protected $id;

    /**
     * @param string|integer $id
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
        return sprintf('delete %d', $this->id);
    }

    /**
     * @return $this
     * @throws NotFoundException
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
    {
        $socket->write($this->getCommand());

        $response = $socket->read();
        switch ($response) {
            case 'DELETED':
                return $this;

            case 'NOT_FOUND':
                throw new NotFoundException("Job id '{$this->id}' could not be found.");

            default:
                throw new CommandException("Delete id '{$this->id}' failed '{$response}'");
        }
    }
}
