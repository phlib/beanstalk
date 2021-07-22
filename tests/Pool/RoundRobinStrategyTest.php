<?php

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RoundRobinStrategyTest extends TestCase
{
    public function testImplementsSelectionStrategyInterface()
    {
        static::assertInstanceOf(SelectionStrategyInterface::class, new RoundRobinStrategy());
    }

    public function testAllowsContinuousSelection()
    {
        $firstHost = 'host123';
        $keys = [$firstHost, 'host456', 'host789'];
        $strategy = new RoundRobinStrategy();
        for ($i = 0; $i < count($keys); $i++) {
            $strategy->pickOne($keys);
        }
        static::assertEquals($firstHost, $strategy->pickOne($keys));
    }

    public function testFailsWhenGivenEmptyCollection()
    {
        $this->expectException(InvalidArgumentException::class);

        (new RoundRobinStrategy())->pickOne([]);
    }

    public function testWithDifferingCollections()
    {
        $strategy = new RoundRobinStrategy();

        $keys = ['host123'];
        $strategy->pickOne($keys);

        $newHost = 'host456';
        $keys[] = $newHost;
        static::assertEquals($newHost, $strategy->pickOne($keys));
    }
}
