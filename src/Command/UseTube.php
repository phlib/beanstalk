<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;

class UseTube implements CommandInterface
{
    use ValidateTrait;

    /**
     * @var string
     */
    protected $tube;

    /**
     * @param string $tube
     */
    public function __construct($tube)
    {
        $this->validateTubeName($tube);
        $this->tube = $tube;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return sprintf('use %s', $this->tube);
    }

    /**
     * @param SocketInterface $socket
     * @return string
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
    {
        $socket->write($this->getCommand());

        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'USING':
                return strtok(' '); // tube name
            default:
                throw new CommandException("Use tube '$this->tube' failed '$status'");
        }
    }
}
