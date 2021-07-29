<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Pool;

/**
 * Interface SelectionStrategyInterface
 * @package Phlib\Beanstalk\Pool
 */
interface SelectionStrategyInterface
{
    /**
     * @param string[] $collection
     */
    public function pickOne(array $collection): string;
}
