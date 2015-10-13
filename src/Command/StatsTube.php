<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\ValidateTrait;

/**
 * Class StatsTube
 * @package Phlib\Beanstalk\Command
 */
class StatsTube implements CommandInterface
{
    use StatsTrait;
    use ValidateTrait;
    use ToStringTrait;

    /**
     * @var string
     */
    protected $tube;

    /**
     * @param string $tube
     */
    public function __construct($tube)
    {
        $this->validateTubeName($tube);
        $this->tube = $tube;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return sprintf('stats-tube %s', $this->tube);
    }
}
