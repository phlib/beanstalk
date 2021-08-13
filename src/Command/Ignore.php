<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\ValidateTrait;

/**
 * Class Ignore
 * @package Phlib\Beanstalk\Command
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

    public function process(SocketInterface $socket): int
    {
        $socket->write($this->getCommand());

        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'WATCHING':
                return (int)strtok(' ');

            case 'NOT_IGNORED':
                throw new CommandException('Can not ignore only tube currently watching.');

            default:
                throw new CommandException("Ignore tube '{$this->tube}' failed '{$status}'");
        }
    }
}
