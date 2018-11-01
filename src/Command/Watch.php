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
     * @return int
     * @throws CommandException
     */
    public function process(SocketInterface $socket): int
    {
        $socket->write($this->getCommand());

        $status = strtok($socket->read(), ' ');
        if ($status !== 'WATCHING') {
            throw new CommandException("Watch tube '$this->tube' failed '$status'");
        }

        return (int)strtok(' ');
    }
}
