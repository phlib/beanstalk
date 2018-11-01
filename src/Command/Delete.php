<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\CommandException;

class Delete implements CommandInterface
{
    /**
     * @var string|int
     */
    protected $id;

    /**
     * @param string|int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return sprintf('delete %d', $this->id);
    }

    /**
     * @param SocketInterface $socket
     * @return $this
     * @throws NotFoundException
     * @throws CommandException
     */
    public function process(SocketInterface $socket): Delete
    {
        $socket->write($this->getCommand());

        $response = $socket->read();
        switch ($response) {
            case 'DELETED':
                return $this;

            case 'NOT_FOUND':
                throw new NotFoundException("Job id '{$this->id}' could not be found.");

            default:
                throw new CommandException("Delete id '{$this->id}' failed '$response'");
        }
    }
}
