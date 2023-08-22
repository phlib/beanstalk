<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * @package Phlib\Beanstalk
 */
class Peek implements CommandInterface
{
    private int $jobId;

    public function __construct(int $jobId)
    {
        $this->jobId = $jobId;
    }

    protected function getCommand(): string
    {
        return sprintf('peek %u', $this->jobId);
    }

    public function process(Socket $socket): array
    {
        $socket->write($this->getCommand());

        $response = strtok($socket->read(), ' ');
        switch ($response) {
            case 'FOUND':
                $id = (int)strtok(' ');
                $bytes = (int)strtok(' ');
                $body = substr($socket->read($bytes + 2), 0, -2);

                return [
                    'id' => $id,
                    'body' => $body,
                ];

            case 'NOT_FOUND':
                if (isset($this->jobId)) {
                    throw new NotFoundException(
                        sprintf(NotFoundException::JOB_ID_MSG_F, $this->jobId),
                        NotFoundException::JOB_ID_CODE,
                    );
                }
                throw new NotFoundException(
                    NotFoundException::PEEK_STATUS_MSG,
                    NotFoundException::PEEK_STATUS_CODE,
                );

            default:
                throw new CommandException("Unknown peek response '{$response}'");
        }
    }
}
