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
     * @var string[]
     */
    protected $collection = null;

    /**
     * @var string
     */
    protected $check = '';

    /**
     * @var integer
     */
    protected $index = null;

    /**
     * @inheritdoc
     */
    public function pickOne(array $collection)
    {
        if (empty($collection)) {
            throw new InvalidArgumentException('Can not select from an empty collection.');
        }
        $this->setup($collection);

        $index = $this->index;
        $this->index++;
        if ($this->index > (count($this->collection) - 1)) {
            $this->index = 0;
        }

        return $this->collection[$index];
    }

    /**
     * @param string[] $collection
     */
    protected function setup(array $collection)
    {
        if (!is_null($this->collection) && serialize($collection) == $this->check) {
            return;
        }

        $this->collection = $collection;
        $this->check      = serialize($collection);
        if (is_null($this->index) || $this->index > (count($this->collection) - 1)) {
            $this->index = 0;
        }
    }
}
