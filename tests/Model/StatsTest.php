<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Model;

use PHPUnit\Framework\TestCase;

class StatsTest extends TestCase
{
    private const LIST_STATS = [
        'pid',
        'version',
        'uptime',
        'binlog-current-index',
        'draining',
        'id',
        'hostname',
        'os',
        'platform',
        'name',
    ];

    private const MAX_STATS = [
        'max-job-size',
        'binlog-max-size',
        'binlog-oldest-index',
    ];

    public function testToArrayReturnsOriginalData(): void
    {
        $data = [
            'job-timeouts' => rand(),
            'binlog-max-size' => rand(),
        ];
        $stats = new Stats($data);
        static::assertSame($data, $stats->toArray());
    }

    public function testIsEmptyWithEmptyData(): void
    {
        $stats = new Stats();
        static::assertTrue($stats->isEmpty());
    }

    public function testIsEmptyWithData(): void
    {
        $stats = new Stats([
            'job-timeouts' => rand(),
            'binlog-max-size' => rand(),
        ]);
        static::assertFalse($stats->isEmpty());
    }

    public function testAggregateNewData(): void
    {
        $value1 = uniqid('val1');
        $value2 = uniqid('val2');
        $value3 = uniqid('val3');
        $value4 = uniqid('val4');

        $stats = new Stats([
            'key1' => $value1,
            'key2' => $value2,
        ]);
        $stats = $stats->aggregate([
            'key3' => $value3,
            'key4' => $value4,
        ]);

        static::assertSame(
            [
                'key1' => $value1,
                'key2' => $value2,
                'key3' => $value3,
                'key4' => $value4,
            ],
            $stats->toArray()
        );
    }

    public function testAggregateIsImmutable(): void
    {
        $value1 = uniqid('val1');
        $value2 = uniqid('val2');

        $stats = new Stats([
            'key1' => $value1,
        ]);

        $statsNew = $stats->aggregate([
            'key2' => $value2,
        ]);

        static::assertSame(
            [
                'key1' => $value1,
            ],
            $stats->toArray()
        );

        static::assertSame(
            [
                'key1' => $value1,
                'key2' => $value2,
            ],
            $statsNew->toArray()
        );
    }

    /**
     * @dataProvider dataAggregateListData
     */
    public function testAggregateListData(string $name): void
    {
        $value1 = uniqid('val1');
        $value2 = uniqid('val2');

        $stats = new Stats([
            $name => $value1,
        ]);
        $stats = $stats->aggregate([
            $name => $value2,
        ]);

        $expected = [
            $name => $value1 . ',' . $value2,
        ];

        static::assertSame($expected, $stats->toArray());
    }

    public function dataAggregateListData(): iterable
    {
        foreach (self::LIST_STATS as $name) {
            yield $name => [$name];
        }
    }

    /**
     * @dataProvider dataAggregateMaxData
     */
    public function testAggregateMaxData(string $name): void
    {
        $value1 = rand();
        $value2 = rand();

        $stats = new Stats([
            $name => $value1,
        ]);
        $stats = $stats->aggregate([
            $name => $value2,
        ]);

        $expected = [
            $name => max($value1, $value2),
        ];

        static::assertSame($expected, $stats->toArray());
    }

    public function dataAggregateMaxData(): iterable
    {
        foreach (self::MAX_STATS as $name) {
            yield $name => [$name];
        }
    }

    /**
     * @dataProvider dataAggregateSum
     */
    public function testAggregateSum(string $name, int $value1, int $value2, int $value3): void
    {
        $stats = new Stats([
            $name => $value1,
        ]);
        $stats = $stats->aggregate([
            $name => $value2,
        ]);
        $stats = $stats->aggregate([
            $name => $value3,
        ]);

        $expected = [
            $name => $value1 + $value2 + $value3,
        ];

        static::assertSame($expected, $stats->toArray());
    }

    public function dataAggregateSum(): iterable
    {
        return [
            'basic' => [
                sha1(uniqid('key')),
                rand(1, 4096),
                rand(1, 4096),
                rand(1, 4096),
            ],
            'zero-first' => [
                sha1(uniqid('key')),
                0,
                rand(1, 4096),
                rand(1, 4096),
            ],
            'zero-mid' => [
                sha1(uniqid('key')),
                rand(1, 4096),
                0,
                rand(1, 4096),
            ],
            'zero-final' => [
                sha1(uniqid('key')),
                rand(1, 4096),
                rand(1, 4096),
                0,
            ],
        ];
    }
}
