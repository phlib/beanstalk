<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\BuriedException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\CommandException;

class Release implements CommandInterface
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
     * @var int
     */
    protected $delay;

    /**
     * @param string  $id
     * @param int $priority
     * @param int $delay
     */
    public function __construct($id, int $priority, int $delay)
    {
        $this->validatePriority($priority);

        $this->id       = $id;
        $this->priority = $priority;
        $this->delay    = $delay;
    }

    /**
     * @return string
     */
    public function getCommand(): string
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
                return $this;
            case 'BURIED':
                throw BuriedException::create($this->id);
            case 'NOT_FOUND':
                throw new NotFoundException("Job id '$this->id' could not be found.");
            default:
                throw new CommandException("Release '$this->id' failed '$response'");
        }
    }
}
