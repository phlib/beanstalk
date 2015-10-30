<?php

namespace Phlib\Beanstalk\Pool;

/**
 * Class RandomStrategy
 * @package Phlib\Beanstalk\Pool
 */
class RandomStrategy implements SelectionStrategyInterface
{
    public function pickOne($collection)
    {
//        $keys = array_keys($this->connections);
//        shuffle($keys);
//        return $keys;
    }
}
