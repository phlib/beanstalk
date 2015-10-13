<?php

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
     * @var string|integer
     */
    protected $id;

    /**
     * @param integer|string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return sprintf('stats-job %d', $this->id);
    }
}
