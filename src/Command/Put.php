<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\BuriedException;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\DrainingException;

/**
 * @package Phlib\Beanstalk
 */
class Put implements CommandInterface
{
    use ValidateTrait;

    private string $data;

    private int $priority;

    private int $delay;

    private int $ttr;

    public function __construct(string $data, int $priority, int $delay, int $ttr)
    {
        $this->validateJobData($data);
        $this->validatePriority($priority);
        $this->validateDelay($delay);
        $this->validateTtr($ttr);

        $this->data = $data;
        $this->priority = $priority;
        $this->delay = $delay;
        $this->ttr = $ttr;
    }

    private function getCommand(): string
    {
        $bytesSent = strlen($this->data);
        return sprintf('put %d %d %d %d', $this->priority, $this->delay, $this->ttr, $bytesSent);
    }

    public function process(Socket $socket): int
    {
        $socket->write($this->getCommand());
        $socket->write($this->data);

        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'INSERTED':
                return (int)strtok(' '); // job id
            case 'BURIED':
                $jobId = (int)strtok(' ');
                throw BuriedException::create($jobId);
            case 'DRAINING':
                throw new DrainingException(
                    DrainingException::PUT_MSG,
                    DrainingException::PUT_CODE,
                );
            case 'EXPECTED_CRLF':
            case 'JOB_TOO_BIG':
            default:
                throw new CommandException("Put failed '{$status}'");
        }
    }
}
