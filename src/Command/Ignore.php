<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\ValidateTrait;

/**
 * Class Ignore
 * @package Phlib\Beanstalk\Command
 */
class Ignore implements CommandInterface
{
    use ValidateTrait;
    use ToStringTrait;

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
    public function getCommand()
    {
        return sprintf('ignore %s', $this->tube);
    }

    /**
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

            case 'NOT_IGNORED':
                throw new CommandException('Can not ignore only tube currently watching.');

            default:
                throw new CommandException("Ignore tube '{$this->tube}' failed '{$status}'");
        }
    }
}
