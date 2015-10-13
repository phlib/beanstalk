<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\SocketInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\CommandException;

/**
 * Class Peek
 * @package Phlib\Beanstalk\Command
 */
class Peek implements CommandInterface
{
    use ToStringTrait;

    const READY   = 'ready';
    const DELAYED = 'delayed';
    const BURIED  = 'buried';

    /**
     * @var string|integer
     */
    protected $jobId = null;

    /**
     * @var string
     */
    protected $subCommand = null;

    /**
     * @var array
     */
    protected $subCommands = [
        self::READY,
        self::DELAYED,
        self::BURIED
    ];

    /**
     * @param string $subject
     * @throws InvalidArgumentException
     */
    public function __construct($subject)
    {
        if (is_int($subject) || ctype_digit($subject)) {
            $this->jobId = $subject;
        } elseif (in_array($subject, $this->subCommands)) {
            $this->subCommand = $subject;
        } else {
            throw new InvalidArgumentException(sprintf('Invalid peek subject: %s', $subject));
        }
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return isset($this->jobId) ?
            sprintf('peek %u', $this->jobId) :
            sprintf('peek-%s', $this->subCommand);
    }

    /**
     * @param SocketInterface $socket
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
                $id     = (int)strtok(' ');
                $bytes  = (int)strtok(' ');
                $body   = substr($socket->read($bytes + 2), 0, -2);

                return ['id' => $id, 'body' => $body];

            case 'NOT_FOUND':
                throw new NotFoundException("Peek failed find any jobs");

            default:
                throw new CommandException("Unknown peek response '$response'");
        }
    }
}
