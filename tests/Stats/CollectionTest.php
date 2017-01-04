<?php

namespace Phlib\Beanstalk\Tests\Stats;

use Phlib\Beanstalk\Stats\Collection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateStatsWithData()
    {
        $data = [
            'timeouts'        => 5,
            'binlog-max-size' => 10,
        ];
        $stats = new Collection($data);
        $this->assertInstanceOf(Collection::class, $stats);
    }
    
    public function testToArrayReturnsOriginalData()
    {
        $data = [
            'timeouts'        => 5,
            'binlog-max-size' => 10,
        ];
        $stats = new Collection($data);
        $this->assertSame($data, $stats->toArray());
    }
    
    public function testIsEmptyWithEmptyData()
    {
        $stats = new Collection();
        $this->assertTrue($stats->isEmpty());
        $stats = new Collection([]);
        $this->assertTrue($stats->isEmpty());
    }
    
    public function testIsEmptyWithData()
    {
        $stats = new Collection([
            'timeouts'        => 5,
            'binlog-max-size' => 10,
        ]);
        $this->assertFalse($stats->isEmpty());
    }
    
    public function testAddStatsNewData()
    {
        $stats = new Collection([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);
        $stats = $stats->merge([
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
        $stats = new Collection([
            'version' => '1.0.0',
            'name'    => 'first',
        ]);
        $stats = $stats->merge([
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
        $stats = new Collection([
            'timeouts'        => 5,
            'binlog-max-size' => 10,
        ]);
        $stats = $stats->merge([
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
        $stats = new Collection([
            'sumvalue1' => 5,
            'sumvalue2' => 3,
        ]);
        $stats = $stats->merge([
            'sumvalue1' => 14,
            'sumvalue2' => 1,
        ]);
        $this->assertSame([
            'sumvalue1' => 19,
            'sumvalue2' => 4,
        ], $stats->toArray());;
    }
}
