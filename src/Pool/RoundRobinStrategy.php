<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Exception\InvalidArgumentException;

/**
 * Class RoundRobinStrategy
 * @package Phlib\Beanstalk\Pool
 */
class RoundRobinStrategy implements SelectionStrategyInterface
{
    protected int $index = -1;

    /**
     * @param string[] $collection
     */
    public function pickOne(array $collection): string
    {
        if (empty($collection)) {
            throw new InvalidArgumentException('Can not select from an empty collection.');
        }

        $this->index = ($this->index + 1) % count($collection);
        return $collection[$this->index];
    }
}
