<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Exception;

class BuriedException extends CommandException
{
    public static function create(int $jobId): self
    {
        return new static($jobId, 'Server ran out of memory trying to grow the priority queue data structure');
    }

    private int $jobId;

    public function __construct(int $jobId, string $message, int $code = 0, ?\Exception $previous = null)
    {
        $this->jobId = $jobId;
        parent::__construct($message, $code, $previous);
    }

    public function getJobId(): int
    {
        return $this->jobId;
    }
}
