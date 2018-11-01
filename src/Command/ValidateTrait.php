<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

trait ValidateTrait
{
    /**
     * @param int $priority
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validatePriority(int $priority): bool
    {
        $options = ['options' => ['min_range' => 0, 'max_range' => ConnectionInterface::MAX_PRIORITY]];
        if (filter_var($priority, FILTER_VALIDATE_INT, $options) === false) {
            throw new InvalidArgumentException('Priority is not within acceptable range.');
        }
        return true;
    }

    /**
     * @param string $name
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateTubeName(string $name): bool
    {
        $bytes   = \strlen($name);
        $options = ['options' => ['min_range' => 1, 'max_range' => ConnectionInterface::MAX_TUBE_LENGTH]];
        if (filter_var($bytes, FILTER_VALIDATE_INT, $options) === false) {
            throw new InvalidArgumentException("Specified tube '$name' is not a valid name.");
        }
        return true;
    }

    /**
     * @param mixed $data
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateJobData($data): bool
    {
        if (\is_array($data)) {
            $data = 'Array';
        } elseif (\is_object($data)) {
            if (method_exists($data, '__toString')) {
                $data = (string)$data;
            } else {
                $data = 'Object';
            }
        }
        $options = ['options' => ['min_range' => 1, 'max_range' => ConnectionInterface::MAX_JOB_LENGTH]];
        if (filter_var(\strlen((string)$data), FILTER_VALIDATE_INT, $options) === false) {
            throw new InvalidArgumentException('The job data is too large. Maximum allowed size is 65,536.');
        }
        return true;
    }
}
