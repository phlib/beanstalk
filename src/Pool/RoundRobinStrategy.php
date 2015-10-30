<?php

namespace Phlib\Beanstalk\Pool;

/**
 * Class RoundRobinStrategy
 * @package Phlib\Beanstalk\Pool
 */
class RoundRobinStrategy implements SelectionStrategyInterface
{
    protected $index = null;
    public function pickOne($collection)
    {
    }
}
