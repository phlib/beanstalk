<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Exception;

class BuriedException extends CommandException
{
    /**
     * @var int
     */
    private $jobId;

    /**
     * @param int $jobId
     * @return static
     */
    public static function create($jobId)
    {
        return new static($jobId, "Server ran out of memory trying to grow the priority queue data structure.");
    }

    /**
     * BuriedException constructor.
     * @param int $jobId
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($jobId, string $message, int $code = 0, \Exception $previous = null)
    {
        $this->jobId = $jobId;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int
     */
    public function getJobId(): int
    {
        return $this->jobId;
    }
}
