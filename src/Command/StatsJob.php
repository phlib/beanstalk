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

    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    private function getCommand(): string
    {
        return sprintf('stats-job %d', $this->id);
    }
}
