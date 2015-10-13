<?php

namespace Phlib\Beanstalk;

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
        $options = ['options' => ['min_range' => 0, 'max_range' => 4294967295]]; // 2^32
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
        $bytes   = strlen($name);
        $options = ['options' => ['min_range' => 1, 'max_range' => 200]];
        if (filter_var($bytes, FILTER_VALIDATE_INT, $options) === false) {
            throw new InvalidArgumentException("Specified tube '$name' is not a valid name.");
        }
        return true;
    }
}
