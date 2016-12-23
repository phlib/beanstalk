<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\BuriedException;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\DrainingException;

class Put implements CommandInterface
{
    use ValidateTrait;

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

        $this->data     = (string)$data;
        $this->priority = $priority;
        $this->delay    = $delay;
        $this->ttr      = $ttr;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        $bytesSent = strlen($this->data);
        return sprintf('put %d %d %d %d', $this->priority, $this->delay, $this->ttr, $bytesSent);
    }

    /**
     * @param SocketInterface $socket
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
                return (int)strtok(' '); // job id
            case 'BURIED':
                $jobId = (int)strtok(' ');
                throw BuriedException::create($jobId);
            case 'DRAINING':
                throw new DrainingException('Server in a draining status.');
            case 'EXPECTED_CRLF':
            case 'JOB_TOO_BIG':
            default:
                throw new CommandException("Put failed '$status'.");
        }
    }
}
