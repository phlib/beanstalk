<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\SocketInterface;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\CommandException;

/**
 * Class AbstractStats
 * @package Phlib\Beanstalk\Command
 */
trait StatsTrait
{

    /**
     * @param SocketInterface $socket
     * @return array
     * @throws NotFoundException
     * @throws CommandException
     */
    public function process(SocketInterface $socket)
    {
        $socket->write($this->getCommand());
        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'OK':
                $data = substr($socket->read((int)strtok(' ') + 2), 0, -2);
                return $this->decode($data);

            case 'NOT_FOUND':
                throw new NotFoundException("Stats read failed '$status'");

            default:
                throw new CommandException("Stats read failed '$status'");
        }
    }

    /**
     * Decodes the YAML string into an array of data.
     *
     * @param  string $response
     * @return array
     */
    protected function decode($response)
    {
        $lines = array_slice(explode("\n", trim($response)), 1);

        $result = [];
        foreach ($lines as $line) {
            if ($line[0] == '-') {
                $result[] = trim(ltrim($line, '- '));
            } else {
                $key = strtok($line, ': ');
                if ($key) {
                    $value = ltrim(trim(strtok('')), ' ');

                    if (is_numeric($value)) {
                        $value = $value + 0;
                    }

                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }
}
