<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\ValidateTrait;

/**
 * @package Phlib\Beanstalk
 */
class Bury implements CommandInterface
{
    use ValidateTrait;

    private int $id;

    private int $priority;

    public function __construct(int $id, int $priority)
    {
        $this->validatePriority($priority);

        $this->id = $id;
        $this->priority = $priority;
    }

    private function getCommand(): string
    {
        return sprintf('bury %d %d', $this->id, $this->priority);
    }

    public function process(SocketInterface $socket): self
    {
        $socket->write($this->getCommand());

        $response = $socket->read();
        switch ($response) {
            case 'BURIED':
                return $this;

            case 'NOT_FOUND':
                throw new NotFoundException("Job id '{$this->id}' could not be found.");

            default:
                throw new CommandException("Bury id '{$this->id}' failed '{$response}'");
        }
    }
}
