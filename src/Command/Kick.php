<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\CommandException;

/**
 * @package Phlib\Beanstalk
 */
class Kick implements CommandInterface
{
    private int $bound;

    public function __construct(int $bound)
    {
        $this->bound = $bound;
    }

    private function getCommand(): string
    {
        return sprintf('kick %d', $this->bound);
    }

    public function process(Socket $socket): int
    {
        $socket->write($this->getCommand());
        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'KICKED':
                return (int)strtok(' ');

            default:
                throw new CommandException("Kick with bound '{$this->bound}' failed '{$status}'");
        }
    }
}
