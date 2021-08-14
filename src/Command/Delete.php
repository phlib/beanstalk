<?php

declare(strict_types=1);

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
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    private function getCommand(): string
    {
        return sprintf('delete %d', $this->id);
    }

    public function process(SocketInterface $socket): self
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
