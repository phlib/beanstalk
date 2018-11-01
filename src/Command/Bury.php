<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\CommandException;

class Bury implements CommandInterface
{
    use ValidateTrait;

    /**
     * @var string|int
     */
    protected $id;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @param string|int $id
     * @param int $priority
     */
    public function __construct($id, int $priority)
    {
        $this->validatePriority($priority);

        $this->id       = $id;
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return sprintf('bury %d %d', $this->id, $this->priority);
    }

    /**
     * @param SocketInterface $socket
     * @return $this
     * @throws NotFoundException
     * @throws CommandException
     */
    public function process(SocketInterface $socket): Bury
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
