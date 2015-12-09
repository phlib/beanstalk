<?php

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Exception\InvalidArgumentException;

/**
 * Class RoundRobinStrategy
 * @package Phlib\Beanstalk\Pool
 */
class RoundRobinStrategy implements SelectionStrategyInterface
{
    /**
     * @var integer
     */
    protected $index = -1;

    /**
     * @inheritdoc
     */
    public function pickOne(array $collection)
    {
        if (empty($collection)) {
            throw new InvalidArgumentException('Can not select from an empty collection.');
        }

        $this->index = ($this->index + 1) % count($collection);
        return $collection[$this->index];
    }
}
