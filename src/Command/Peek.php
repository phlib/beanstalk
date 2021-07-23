<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * Class Peek
 * @package Phlib\Beanstalk\Command
 */
class Peek implements CommandInterface
{
    use ToStringTrait;

    protected int $jobId;

    public function __construct(int $jobId)
    {
        $this->jobId = $jobId;
    }

    public function getCommand(): string
    {
        return sprintf('peek %u', $this->jobId);
    }

    /**
     * @return array
     * @throws NotFoundException
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
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
