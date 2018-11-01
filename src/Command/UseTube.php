<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\ValidateTrait;

/**
 * @package Phlib\Beanstalk
 */
class UseTube implements CommandInterface
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
        return sprintf('use %s', $this->tube);
    }

    public function process(SocketInterface $socket): string
    {
        $socket->write($this->getCommand());
        $status = strtok($socket->read(), ' ');

        if ($status !== 'USING') {
            throw new CommandException("Use tube '{$this->tube}' failed '{$status}'");
        }

        $tubeName = strtok(' ');
        if ($tubeName === false) {
            return '';
        }
        return $tubeName;
    }
}
