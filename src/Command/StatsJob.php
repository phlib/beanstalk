<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

/**
 * Class StatsJob
 * @package Phlib\Beanstalk\Command
 */
class StatsJob implements CommandInterface
{
    use StatsTrait;
    use ToStringTrait;

    /**
     * @var string|int
     */
    protected $id;

    /**
     * @param int|string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getCommand(): string
    {
        return sprintf('stats-job %d', $this->id);
    }
}
