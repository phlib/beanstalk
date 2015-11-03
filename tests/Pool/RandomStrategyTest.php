<?php

namespace Phlib\Tests\Pool;

use Phlib\Beanstalk\Pool\RandomStrategy;

class RandomStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testImplementsSelectionStrategyInterface()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Pool\SelectionStrategyInterface', new RandomStrategy());
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testFailsWhenGivenEmptyCollection()
    {
        (new RandomStrategy())->pickOne([]);
    }

    public function testAllowsContinuousSelection()
    {
        $keys = ['host123', 'host456', 'host789'];
        $strategy = new RandomStrategy();
        for ($i = 0; $i < count($keys); $i++) {
            $strategy->pickOne($keys);
        }
        $this->assertContains($strategy->pickOne($keys), $keys);
    }

    public function testWithDifferingCollections()
    {
        $strategy = new RandomStrategy();

        $keys = ['host123'];
        $strategy->pickOne($keys);

        $newHost = 'host456';
        $keys[] = $newHost;
        $this->assertContains($strategy->pickOne($keys), $keys);
    }
}
