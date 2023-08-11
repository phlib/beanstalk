<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

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

    public function process(Socket $socket): void
    {
        $socket->write($this->getCommand());

        $response = $socket->read();
        switch ($response) {
            case 'BURIED':
                return;

            case 'NOT_FOUND':
                throw new NotFoundException(
                    sprintf(NotFoundException::JOB_ID_MSG_F, $this->id),
                    NotFoundException::JOB_ID_CODE,
                );

            default:
                throw new CommandException("Bury id '{$this->id}' failed '{$response}'");
        }
    }
}
