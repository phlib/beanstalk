<?php

declare(strict_types=1);

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

    protected int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getCommand(): string
    {
        return sprintf('touch %d', $this->id);
    }

    public function process(SocketInterface $socket): self
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
