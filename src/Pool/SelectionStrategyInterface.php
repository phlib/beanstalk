<?php

namespace Phlib\Beanstalk\Pool;

/**
 * Interface SelectionStrategyInterface
 * @package Phlib\Beanstalk\Pool
 */
interface SelectionStrategyInterface
{
    public function pickOne($collection);
}
