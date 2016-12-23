<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

class StatsJob implements CommandInterface
{
    use StatsTrait;

    /**
     * @var string|int
     */
    protected $id;

    /**
     * @param string|int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return sprintf('stats-job %d', $this->id);
    }
}
