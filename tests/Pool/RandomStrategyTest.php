<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RandomStrategyTest extends TestCase
{
    public function testImplementsSelectionStrategyInterface(): void
    {
        static::assertInstanceOf(SelectionStrategyInterface::class, new RandomStrategy());
    }

    public function testFailsWhenGivenEmptyCollection(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RandomStrategy())->pickOne([]);
    }

    public function testAllowsContinuousSelection(): void
    {
        $keys = ['host123', 'host456', 'host789'];
        $strategy = new RandomStrategy();
        for ($i = 0; $i < count($keys); $i++) {
            $strategy->pickOne($keys);
        }
        static::assertContains($strategy->pickOne($keys), $keys);
    }

    public function testWithDifferingCollections(): void
    {
        $strategy = new RandomStrategy();

        $keys = ['host123'];
        $strategy->pickOne($keys);

        $newHost = 'host456';
        $keys[] = $newHost;
        static::assertContains($strategy->pickOne($keys), $keys);
    }
}
