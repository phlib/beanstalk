<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * Class Peek
 * @package Phlib\Beanstalk\Command
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

    public function process(SocketInterface $socket): array
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
                throw new NotFoundException('Peek failed to find any jobs');

            default:
                throw new CommandException("Unknown peek response '{$response}'");
        }
    }
}
