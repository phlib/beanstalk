<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Exception\InvalidArgumentException;

class RandomStrategy implements SelectionStrategyInterface
{
    /**
     * @inheritdoc
     */
    public function pickOne(array $collection)
    {
        if (empty($collection)) {
            throw new InvalidArgumentException('Can not select from an empty collection.');
        }
        if (count($collection) == 1) {
            return current($collection);
        }

        return $collection[array_rand($collection)];
    }
}
