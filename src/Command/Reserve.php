<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;

/**
 * Class Reserve
 * @package Phlib\Beanstalk\Command
 */
class Reserve implements CommandInterface
{
    private ?int $timeout;

    public function __construct(?int $timeout = null)
    {
        $this->timeout = $timeout;
    }

    private function getCommand(): string
    {
        if ($this->timeout === null) {
            return 'reserve';
        }

        return sprintf('reserve-with-timeout %d', $this->timeout);
    }

    public function process(SocketInterface $socket): ?array
    {
        $socket->write($this->getCommand());
        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'RESERVED':
                $id = (int)strtok(' ');
                $bytes = (int)strtok(' ');
                $body = substr($socket->read($bytes + 2), 0, -2);

                return [
                    'id' => $id,
                    'body' => $body,
                ];

            case 'DEADLINE_SOON':
            case 'TIMED_OUT':
                return null;

            default:
                throw new CommandException("Reserve failed '{$status}'");
        }
    }
}
