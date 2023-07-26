<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Exception\InvalidArgumentException;

/**
 * @package Phlib\Beanstalk
 */
class RandomStrategy implements SelectionStrategyInterface
{
    /**
     * @param string[] $collection
     */
    public function pickOne(array $collection): string
    {
        if (empty($collection)) {
            throw new InvalidArgumentException('Can not select from an empty collection.');
        }
        if (count($collection) === 1) {
            return current($collection);
        }

        return $collection[array_rand($collection)];
    }
}
