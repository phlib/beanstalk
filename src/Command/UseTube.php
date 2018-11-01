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
        return sprintf('use %s', $this->tube);
    }

    /**
     * @param SocketInterface $socket
     * @return string
     * @throws CommandException
     */
    public function process(SocketInterface $socket): string
    {
        $socket->write($this->getCommand());

        $status = strtok($socket->read(), ' ');
        if ($status !== 'USING') {
            throw new CommandException("Use tube '$this->tube' failed '$status'");
        }

        $result = strtok(' '); // tube name
        if ($result === false) {
            return '';
        }
        return $result;
    }
}
