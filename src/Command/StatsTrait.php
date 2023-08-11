<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

/**
 * @package Phlib\Beanstalk
 */
trait StatsTrait
{
    public function process(Socket $socket): array
    {
        $socket->write($this->getCommand());
        $status = strtok($socket->read(), ' ');
        switch ($status) {
            case 'OK':
                $data = substr($socket->read((int)strtok(' ') + 2), 0, -2);
                return $this->decode($data);

            case 'NOT_FOUND':
                throw new NotFoundException(
                    NotFoundException::STATS_MSG,
                    NotFoundException::STATS_CODE,
                );

            default:
                throw new CommandException("Stats read failed '{$status}'");
        }
    }

    /**
     * Decodes the YAML string into an array of data.
     */
    private function decode(string $response): array
    {
        $lines = array_slice(explode("\n", trim($response)), 1);

        $result = [];
        foreach ($lines as $line) {
            if ($line[0] === '-') {
                $value = trim(ltrim($line, '- '));

                if (is_numeric($value)) {
                    $value = $value + 0;
                }

                $result[] = $value;
            } else {
                $key = strtok($line, ': ');
                if ($key) {
                    $value = ltrim(trim(strtok('')), ' ');

                    if (is_numeric($value)) {
                        $value += 0;
                    }

                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }
}
