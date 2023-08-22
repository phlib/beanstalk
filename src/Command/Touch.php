<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * @package Phlib\Beanstalk
 */
class Touch implements CommandInterface
{
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    private function getCommand(): string
    {
        return sprintf('touch %d', $this->id);
    }

    public function process(Socket $socket): void
    {
        $socket->write($this->getCommand());

        $status = $socket->read();
        switch ($status) {
            case 'TOUCHED':
                return;

            case 'NOT_TOUCHED':
                throw new NotFoundException(
                    sprintf(NotFoundException::JOB_ID_MSG_F, $this->id),
                    NotFoundException::JOB_ID_CODE,
                );

            default:
                throw new CommandException("Touch id '{$this->id}' failed '{$status}'");
        }
    }
}
