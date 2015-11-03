<?php

namespace Phlib\Beanstalk\Pool;

/**
 * Interface SelectionStrategyInterface
 * @package Phlib\Beanstalk\Pool
 */
interface SelectionStrategyInterface
{
    /**
     * @param string[] $collection
     * @return string
     */
    public function pickOne(array $collection);
}
