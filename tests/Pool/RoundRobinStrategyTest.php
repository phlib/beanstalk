<?php

namespace Phlib\Beanstalk\Pool;

class RoundRobinStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testImplementsSelectionStrategyInterface()
    {
        $this->assertInstanceOf(SelectionStrategyInterface::class, new RoundRobinStrategy());
    }

    public function testAllowsContinuousSelection()
    {
        $firstHost = 'host123';
        $keys = [$firstHost, 'host456', 'host789'];
        $strategy = new RoundRobinStrategy();
        for ($i = 0; $i < count($keys); $i++) {
            $strategy->pickOne($keys);
        }
        $this->assertEquals($firstHost, $strategy->pickOne($keys));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testFailsWhenGivenEmptyCollection()
    {
        (new RoundRobinStrategy())->pickOne([]);
    }

    public function testWithDifferingCollections()
    {
        $strategy = new RoundRobinStrategy();

        $keys = ['host123'];
        $strategy->pickOne($keys);

        $newHost = 'host456';
        $keys[] = $newHost;
        $this->assertEquals($newHost, $strategy->pickOne($keys));
    }
}
