<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\ValidateTrait;

/**
 * Class Release
 * @package Phlib\Beanstalk\Command
 */
class Release implements CommandInterface
{
    use ValidateTrait;
    use ToStringTrait;

    protected int $id;

    protected int $priority;

    protected int $delay;

    public function __construct(int $id, int $priority, int $delay)
    {
        $this->validatePriority($priority);
        $this->validateDelay($delay);

        $this->id = $id;
        $this->priority = $priority;
        $this->delay = $delay;
    }

    public function getCommand(): string
    {
        return sprintf('release %d %d %d', $this->id, $this->priority, $this->delay);
    }

    public function process(SocketInterface $socket): self
    {
        $socket->write($this->getCommand());

        $response = $socket->read();
        switch ($response) {
            case 'RELEASED':
            case 'BURIED':
                return $this;

            case 'NOT_FOUND':
                throw new NotFoundException("Job id '{$this->id}' could not be found.");

            default:
                throw new CommandException("Release '{$this->id}' failed '{$response}'");
        }
    }
}
