<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\CommandException;

/**
 * @package Phlib\Beanstalk
 */
class Watch implements CommandInterface
{
    use ValidateTrait;

    private string $tube;

    public function __construct(string $tube)
    {
        $this->validateTubeName($tube);
        $this->tube = $tube;
    }

    private function getCommand(): string
    {
        return sprintf('watch %s', $this->tube);
    }

    public function process(Socket $socket): int
    {
        $socket->write($this->getCommand());
        $status = strtok($socket->read(), ' ');

        if ($status !== 'WATCHING') {
            throw new CommandException("Watch tube '{$this->tube}' failed '{$status}'");
        }

        return (int)strtok(' ');
    }
}
