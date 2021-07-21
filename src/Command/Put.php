<?php

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
    use ToStringTrait;

    /**
     * @var string
     */
    protected $data;

    /**
     * @var integer
     */
    protected $priority;

    /**
     * @var integer
     */
    protected $delay;

    /**
     * @var integer
     */
    protected $ttr;

    /**
     * @param string  $data
     * @param integer $priority
     * @param integer $delay
     * @param integer $ttr
     */
    public function __construct($data, $priority, $delay, $ttr)
    {
        $this->validateJobData($data);
        $this->validatePriority($priority);

        $this->data = (string)$data;
        $this->priority = $priority;
        $this->delay = $delay;
        $this->ttr = $ttr;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        $bytesSent = strlen($this->data);
        return sprintf('put %d %d %d %d', $this->priority, $this->delay, $this->ttr, $bytesSent);
    }

    /**
     * @return integer
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
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
