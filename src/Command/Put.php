<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\ValidateTrait;

/**
 * Class Put
 * @package Phlib\Beanstalk\Command
 */
class Put implements CommandInterface
{
    use ValidateTrait;

    protected string $data;

    protected int $priority;

    protected int $delay;

    protected int $ttr;

    public function __construct(string $data, int $priority, int $delay, int $ttr)
    {
        $this->validateJobData($data);
        $this->validatePriority($priority);
        $this->validateDelay($delay);
        $this->validateTtr($ttr);

        $this->data = (string)$data;
        $this->priority = $priority;
        $this->delay = $delay;
        $this->ttr = $ttr;
    }

    public function getCommand(): string
    {
        $bytesSent = strlen($this->data);
        return sprintf('put %d %d %d %d', $this->priority, $this->delay, $this->ttr, $bytesSent);
    }

    public function process(SocketInterface $socket): int
    {
        $socket->write($this->getCommand());
        $socket->write($this->data);

        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'INSERTED':
            case 'BURIED':
                return (int)strtok(' '); // job id

            case 'EXPECTED_CRLF':
            case 'JOB_TOO_BIG':
            case 'DRAINING':
            default:
                throw new CommandException("Put failed '{$status}'");
        }
    }
}
