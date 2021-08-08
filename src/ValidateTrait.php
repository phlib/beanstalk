<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

/**
 * Class ValidateTrait
 * @package Phlib\Beanstalk
 */
trait ValidateTrait
{
    public function validatePriority(int $priority): void
    {
        /*
         * https://raw.githubusercontent.com/beanstalkd/beanstalkd/master/doc/protocol.txt
         * "<pri> is an integer < 2**32"
         */
        $options = [
            'options' => [
                'min_range' => 0,
                'max_range' => ConnectionInterface::MAX_PRIORITY,
            ],
        ];
        if (filter_var($priority, FILTER_VALIDATE_INT, $options) === false) {
            throw new InvalidArgumentException('Priority is not within acceptable range.');
        }
    }

    public function validateDelay(int $delay): void
    {
        /*
         * https://raw.githubusercontent.com/beanstalkd/beanstalkd/master/doc/protocol.txt
         * "<delay> is an integer number ... Maximum delay is 2**32-1"
         */
        $options = [
            'options' => [
                'min_range' => 0,
                'max_range' => ConnectionInterface::MAX_DELAY,
            ],
        ];
        if (filter_var($delay, FILTER_VALIDATE_INT, $options) === false) {
            throw new InvalidArgumentException('Delay is not within acceptable range.');
        }
    }

    public function validateTtr(int $ttr): void
    {
        /*
         * https://raw.githubusercontent.com/beanstalkd/beanstalkd/master/doc/protocol.txt
         * "<ttr> -- time to run -- is an integer number ... Maximum ttr is 2**32-1"
         */
        $options = [
            'options' => [
                'min_range' => 0,
                'max_range' => ConnectionInterface::MAX_TTR,
            ],
        ];
        if (filter_var($ttr, FILTER_VALIDATE_INT, $options) === false) {
            throw new InvalidArgumentException('TTR is not within acceptable range.');
        }
    }

    public function validateTubeName(string $name): bool
    {
        $bytes = strlen($name);
        $options = [
            'options' => [
                'min_range' => 1,
                'max_range' => ConnectionInterface::MAX_TUBE_LENGTH,
            ],
        ];
        if (filter_var($bytes, FILTER_VALIDATE_INT, $options) === false) {
            throw new InvalidArgumentException("Specified tube '{$name}' is not a valid name.");
        }
        return true;
    }

    /**
     * @param mixed $data
     */
    public function validateJobData($data): bool
    {
        if (is_array($data)) {
            $data = 'Array';
        } elseif (is_object($data)) {
            if (method_exists($data, '__toString')) {
                $data = (string)$data;
            } else {
                $data = 'Object';
            }
        }
        $options = [
            'options' => [
                'min_range' => 1,
                'max_range' => ConnectionInterface::MAX_JOB_LENGTH,
            ],
        ];
        if (filter_var(strlen((string)$data), FILTER_VALIDATE_INT, $options) === false) {
            throw new InvalidArgumentException('The job data is too large. Maximum allowed size is 65,536.');
        }
        return true;
    }
}
