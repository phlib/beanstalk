<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;

class Watch implements CommandInterface
{
    use ValidateTrait;

    /**
     * @var string
     */
    protected $tube;

    /**
     * @param string $tube
     */
    public function __construct(string $tube)
    {
        $this->validateTubeName($tube);
        $this->tube = $tube;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return sprintf('watch %s', $this->tube);
    }

    /**
     * @param SocketInterface $socket
     * @return integer
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
    {
        $socket->write($this->getCommand());

        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'WATCHING':
                return (int)strtok(' ');

            default:
                throw new CommandException("Watch tube '$this->tube' failed '$status'");
        }
    }
}
