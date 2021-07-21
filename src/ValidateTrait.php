<?php

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

/**
 * Class ValidateTrait
 * @package Phlib\Beanstalk
 */
trait ValidateTrait
{
    /**
     * @param integer $priority
     * @return true
     * @throws InvalidArgumentException
     */
    public function validatePriority($priority)
    {
        $options = [
            'options' => [
                'min_range' => 0,
                'max_range' => ConnectionInterface::MAX_PRIORITY,
            ],
        ];
        if (filter_var($priority, FILTER_VALIDATE_INT, $options) === false) {
            throw new InvalidArgumentException('Priority is not within acceptable range.');
        }
        return true;
    }

    /**
     * @param string $name
     * @return true
     * @throws InvalidArgumentException
     */
    public function validateTubeName($name)
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
     * @return true
     */
    public function validateJobData($data)
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
        if (filter_var(strlen($data), FILTER_VALIDATE_INT, $options) === false) {
            throw new InvalidArgumentException('The job data is too large. Maximum allowed size is 65,536.');
        }
        return true;
    }
}
