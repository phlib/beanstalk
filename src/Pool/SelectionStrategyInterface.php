<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Pool;

/**
 * @package Phlib\Beanstalk
 */
interface SelectionStrategyInterface
{
    /**
     * @param string[] $collection
     */
    public function pickOne(array $collection): string;
}
