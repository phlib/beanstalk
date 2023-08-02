<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Model;

use PHPUnit\Framework\TestCase;

class StatsTest extends TestCase
{
    public function testCreateStatsWithData(): void
    {
        $data = [
            'timeouts' => 5,
            'binlog-max-size' => 10,
        ];
        $stats = new Stats($data);
        static::assertInstanceOf(Stats::class, $stats);
    }

    public function testToArrayReturnsOriginalData(): void
    {
        $data = [
            'timeouts' => 5,
            'binlog-max-size' => 10,
        ];
        $stats = new Stats($data);
        static::assertSame($data, $stats->toArray());
    }

    public function testIsEmptyWithEmptyData(): void
    {
        $stats = new Stats();
        static::assertTrue($stats->isEmpty());

        $stats = new Stats([]);
        static::assertTrue($stats->isEmpty());
    }

    public function testIsEmptyWithData(): void
    {
        $stats = new Stats([
            'timeouts' => 5,
            'binlog-max-size' => 10,
        ]);
        static::assertFalse($stats->isEmpty());
    }

    public function testAddStatsNewData(): void
    {
        $stats = new Stats([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        $stats = $stats->aggregate([
            'key3' => 'value3',
            'key4' => 'value4',
        ]);

        static::assertSame(
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
                'key4' => 'value4',
            ],
            $stats->toArray()
        );
    }

    public function testAddStatsListData(): void
    {
        $stats = new Stats([
            'version' => '1.0.0',
            'name' => 'first',
        ]);

        $stats = $stats->aggregate([
            'version' => '1.0.2',
            'name' => 'second',
        ]);

        static::assertSame(
            [
                'version' => '1.0.0,1.0.2',
                'name' => 'first,second',
            ],
            $stats->toArray()
        );
    }

    public function testAddStatsMaxData(): void
    {
        $stats = new Stats([
            'timeouts' => 5,
            'binlog-max-size' => 10,
        ]);

        $stats = $stats->aggregate([
            'timeouts' => 3,
            'binlog-max-size' => 16,
        ]);

        static::assertSame(
            [
                'timeouts' => 5,
                'binlog-max-size' => 16,
            ],
            $stats->toArray()
        );
    }

    public function testAddStatsSum(): void
    {
        $stats = new Stats([
            'sumvalue1' => 5,
            'sumvalue2' => 3,
        ]);

        $stats = $stats->aggregate([
            'sumvalue1' => 14,
            'sumvalue2' => 1,
        ]);

        static::assertSame(
            [
                'sumvalue1' => 19,
                'sumvalue2' => 4,
            ],
            $stats->toArray()
        );
    }
}
