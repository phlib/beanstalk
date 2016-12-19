<?php

namespace Phlib\Beanstalk\Pool;

interface SelectionStrategyInterface
{
    /**
     * @param string[] $collection
     * @return string
     */
    public function pickOne(array $collection);
}
