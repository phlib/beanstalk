<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * @package Phlib\Beanstalk
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

    public function process(Socket $socket): void
    {
        $socket->write($this->getCommand());

        $response = $socket->read();
        switch ($response) {
            case 'DELETED':
                return;

            case 'NOT_FOUND':
                throw new NotFoundException("Job id '{$this->id}' could not be found.");

            default:
                throw new CommandException("Delete id '{$this->id}' failed '{$response}'");
        }
    }
}
