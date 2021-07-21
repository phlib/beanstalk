<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;

/**
 * Class Kick
 * @package Phlib\Beanstalk\Command
 */
class Kick implements CommandInterface
{
    use ToStringTrait;

    protected int $bound;

    public function __construct(int $bound)
    {
        $this->bound = $bound;
    }

    public function getCommand(): string
    {
        return sprintf('kick %d', $this->bound);
    }

    public function process(SocketInterface $socket): int
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
