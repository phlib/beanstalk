<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\CommandException;

/**
 * @package Phlib\Beanstalk
 */
class Ignore implements CommandInterface
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
        return sprintf('ignore %s', $this->tube);
    }

    public function process(Socket $socket): int
    {
        $socket->write($this->getCommand());

        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'WATCHING':
                return (int)strtok(' ');
            case 'NOT_IGNORED':
                throw new CommandException('Cannot ignore the only tube in the watch list');
            default:
                throw new CommandException("Ignore tube '{$this->tube}' failed '{$status}'");
        }
    }
}
