<?php


namespace Phlib\Beanstalk\Model;


class StatsTest extends \PHPUnit_Framework_TestCase
{

    public function testCreateStatsWithData()
    {
        $data = [
            'timeouts'        => 5,
            'binlog-max-size' => 10,
        ];
        $stats = new Stats($data);
        $this->assertInstanceOf('\Phlib\Beanstalk\Model\Stats', $stats);
    }

    public function testToArrayReturnsOriginalData()
    {
        $data = [
            'timeouts'        => 5,
            'binlog-max-size' => 10,
        ];
        $stats = new Stats($data);
        $this->assertSame($data, $stats->toArray());
    }

    public function testIsEmptyWithEmptyData()
    {
        $stats = new Stats();
        $this->assertTrue($stats->isEmpty());

        $stats = new Stats([]);
        $this->assertTrue($stats->isEmpty());
    }

    public function testIsEmptyWithData()
    {
        $stats = new Stats([
            'timeouts'        => 5,
            'binlog-max-size' => 10,
        ]);
        $this->assertFalse($stats->isEmpty());
    }

    public function testAddStatsNewData()
    {
        $stats = new Stats([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        $stats->addStats([
            'key3' => 'value3',
            'key4' => 'value4',
        ]);

        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => 'value4',
        ], $stats->toArray());
    }

    public function testAddStatsListData()
    {
        $stats = new Stats([
            'version' => '1.0.0',
            'name'    => 'first',
        ]);

        $stats->addStats([
            'version' => '1.0.2',
            'name'    => 'second',
        ]);

        $this->assertSame([
            'version' => '1.0.0,1.0.2',
            'name'    => 'first,second',
        ], $stats->toArray());
    }

    public function testAddStatsMaxData()
    {
        $stats = new Stats([
            'timeouts'        => 5,
            'binlog-max-size' => 10,
        ]);

        $stats->addStats([
            'timeouts'        => 3,
            'binlog-max-size' => 16,
        ]);

        $this->assertSame([
            'timeouts'        => 5,
            'binlog-max-size' => 16,
        ], $stats->toArray());
    }

    public function testAddStatsSum()
    {
        $stats = new Stats([
            'sumvalue1' => 5,
            'sumvalue2' => 3,
        ]);

        $stats->addStats([
            'sumvalue1' => 14,
            'sumvalue2' => 1,
        ]);

        $this->assertSame([
            'sumvalue1' => 19,
            'sumvalue2' => 4,
        ], $stats->toArray());;
    }
}
