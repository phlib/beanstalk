<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\BuriedException;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * @package Phlib\Beanstalk
 */
class Release implements CommandInterface
{
    use ValidateTrait;

    private int $id;

    private int $priority;

    private int $delay;

    public function __construct(int $id, int $priority, int $delay)
    {
        $this->validatePriority($priority);
        $this->validateDelay($delay);

        $this->id = $id;
        $this->priority = $priority;
        $this->delay = $delay;
    }

    private function getCommand(): string
    {
        return sprintf('release %d %d %d', $this->id, $this->priority, $this->delay);
    }

    public function process(Socket $socket): self
    {
        $socket->write($this->getCommand());

        $response = $socket->read();
        switch ($response) {
            case 'RELEASED':
                return $this;
            case 'BURIED':
                throw BuriedException::create($this->id);
            case 'NOT_FOUND':
                throw new NotFoundException("Job id '{$this->id}' could not be found.");
            default:
                throw new CommandException("Release '{$this->id}' failed '{$response}'");
        }
    }
}
