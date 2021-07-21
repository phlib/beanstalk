<?php

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Pool\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var Collection|MockObject
     */
    protected $collection;

    protected function setUp()
    {
        parent::setUp();

        $this->collection = $this->createMock(Collection::class);
        $this->pool = new Pool($this->collection);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->servers = null;
        $this->pool = null;
    }

    public function testDisconnectCallsAllConnections()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::exactly(2))
            ->method('disconnect')
            ->willReturn(true);
        $collection = new \ArrayIterator([$connection, $connection]);
        $this->collection->expects(static::any())
            ->method('getIterator')
            ->willReturn($collection);
        $this->pool->disconnect();
    }

    /**
     * @param bool $expected
     * @dataProvider disconnectReturnsValueDataProvider
     */
    public function testDisconnectReturnsValue($expected, array $returnValues)
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::any())
            ->method('disconnect')
            ->willReturnOnConsecutiveCalls(...$returnValues);
        $collection = new \ArrayIterator([$connection, $connection]);
        $this->collection->expects(static::any())
            ->method('getIterator')
            ->willReturn($collection);
        static::assertEquals($expected, $this->pool->disconnect());
    }

    public function disconnectReturnsValueDataProvider()
    {
        return [
            [true, [true, true]],
            [false, [false, true]],
            [false, [true, false]],
            [false, [false, false]],
        ];
    }

    public function testUseTubeCallsAllConnections()
    {
        $tube = 'test-tube';
        $this->collection->expects(static::once())
            ->method('sendToAll');
        $this->pool->useTube($tube);
    }

    public function testIgnoreDoesNotAllowLessThanOneWatching()
    {
        // 'default' tube is already being watched
        static::assertFalse($this->pool->ignore('default'));
    }

    public function testIgnore()
    {
        $this->pool->watch('test-tube');
        static::assertEquals(1, $this->pool->ignore('default'));
    }

    public function testPutSuccess()
    {
        $connection = $this->createMock(Connection::class);
        $this->collection->expects(static::once())
            ->method('sendToOne')
            ->willReturn([
                'connection' => $connection,
                'response' => '123',
            ]);
        $this->pool->put('myJobData');
    }

    public function testPutReturnsJobIdContainingTheServerIdentifier()
    {
        $host = 'host123';
        $connection = $this->createMockConnection($host);
        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->willReturn([
                'connection' => $connection,
                'response' => '123',
            ]);
        static::assertContains($host, $this->pool->put('myJobData'));
    }

    public function testPutReturnsJobIdContainingTheOriginalJobId()
    {
        $jobId = '432';
        $connection = $this->createMockConnection('host:123');
        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->willReturn([
                'connection' => $connection,
                'response' => $jobId,
            ]);
        static::assertContains($jobId, $this->pool->put('myJobData'));
    }

    public function testPutTotalFailure()
    {
        $this->expectException(RuntimeException::class);

        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->willThrowException(new RuntimeException());
        $this->pool->put('myJobData');
    }

    /**
     * @medium
     */
    public function testReserveWithNoJobsDoesNotTakeLongerThanTimeout()
    {
        $connection = $this->createMockConnection('host:123');
        $this->collection->expects(static::any())
            ->method('getAvailableKeys')
            ->willReturn(['host:123', 'host:456']);
        $this->collection->expects(static::any())
            ->method('sendToExact')
            ->willReturn([
                'connection' => $connection,
                'response' => false,
            ]);
        $startTime = time();
        $this->pool->reserve(2);
        $totalTime = time() - $startTime;
        static::assertGreaterThanOrEqual(2, $totalTime);
        static::assertLessThanOrEqual(3, $totalTime);
    }

    public function testReserve()
    {
        $jobId = '123';
        $host = 'host:123';
        $response = [
            'id' => $jobId,
            'body' => 'jobData',
        ];
        $expected = [
            'id' => "{$host}.{$jobId}",
            'body' => 'jobData',
        ];
        $connection = $this->createMockConnection($host);

        $this->collection->expects(static::any())
            ->method('getAvailableKeys')
            ->willReturn([$host]);
        $this->collection->expects(static::any())
            ->method('sendToExact')
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);
        static::assertEquals($expected, $this->pool->reserve());
    }

    public function testReserveWithNoJobsOnFirstServer()
    {
        $jobId = '123';
        $host = 'host:123';
        $response = [
            'id' => $jobId,
            'body' => 'jobData',
        ];
        $expected = [
            'id' => "{$host}.{$jobId}",
            'body' => 'jobData',
        ];
        $connection = $this->createMockConnection($host);

        $this->collection->expects(static::any())
            ->method('getAvailableKeys')
            ->willReturn(['host:456', $host]);
        $this->collection->expects(static::at(0))
            ->method('sendToExact')
            ->willReturn(false); // <-- should ignore this one
        $this->collection->expects(static::at(1))
            ->method('sendToExact')
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);
        static::assertEquals($expected, $this->pool->reserve());
    }

    public function testReserveWithFailingServer()
    {
        $jobId = '123';
        $host = 'host:123';
        $response = [
            'id' => $jobId,
            'body' => 'jobData',
        ];
        $expected = [
            'id' => "{$host}.{$jobId}",
            'body' => 'jobData',
        ];
        $connection = $this->createMockConnection($host);

        $this->collection->expects(static::any())
            ->method('getAvailableKeys')
            ->willReturn(['host:456', $host]);
        $this->collection->expects(static::at(0))
            ->method('sendToExact')
            ->willThrowException(new RuntimeException()); // <-- should continue after this one
        $this->collection->expects(static::at(1))
            ->method('sendToExact')
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);
        static::assertEquals($expected, $this->pool->reserve());
    }

    public function testPoolIdWithInvalidFormat()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->pool->release('123');
    }

    /**
     * @param string $method
     * @dataProvider methodsWithJobIdDataProvider
     */
    public function testMethodsWithJobId($method)
    {
        $host = 'host:456';
        $jobId = '123';
        $this->collection->expects(static::once())
            ->method('sendToExact')
            ->with(
                static::equalTo($host),
                static::equalTo($method),
                static::contains($jobId)
            );
        $this->pool->{$method}("{$host}.{$jobId}");
    }

    public function methodsWithJobIdDataProvider()
    {
        return [['delete'], ['release'], ['bury'], ['touch']];
    }

    public function testPeek()
    {
        $host = 'host:456';
        $jobId = '123';
        $jobBody = 'jobBody';
        $response = [
            'id' => $jobId,
            'body' => $jobBody,
        ];
        $expected = [
            'id' => "{$host}.{$jobId}",
            'body' => $jobBody,
        ];
        $connection = $this->createMockConnection($host);

        $this->collection->expects(static::any())
            ->method('sendToExact')
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);

        static::assertEquals($expected, $this->pool->peek("{$host}.{$jobId}"));
    }

    public function testPeekReady()
    {
        $host = 'host:123';
        $jobId = '123';
        $jobBody = 'jobBody';
        $response = [
            'id' => $jobId,
            'body' => $jobBody,
        ];
        $expected = [
            'id' => "{$host}.{$jobId}",
            'body' => $jobBody,
        ];
        $connection = $this->createMockConnection($host);

        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);

        static::assertEquals($expected, $this->pool->peekReady());
    }

    public function testPeekReadyWithNoReadyJobs()
    {
        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->willReturn([
                'connection' => null,
                'response' => false,
            ]);
        static::assertFalse($this->pool->peekReady());
    }

    public function testPeekDelayed()
    {
        $host = 'host:123';
        $jobId = '123';
        $jobBody = 'jobBody';
        $response = [
            'id' => $jobId,
            'body' => $jobBody,
        ];
        $expected = [
            'id' => "{$host}.{$jobId}",
            'body' => $jobBody,
        ];
        $connection = $this->createMockConnection($host);

        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);

        static::assertEquals($expected, $this->pool->peekDelayed());
    }

    public function testPeekDelayedWithNoDelayedJobs()
    {
        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->willReturn([
                'connection' => null,
                'response' => false,
            ]);
        static::assertFalse($this->pool->peekDelayed());
    }

    public function testPeekBuried()
    {
        $host = 'host:123';
        $jobId = '123';
        $jobBody = 'jobBody';
        $response = [
            'id' => $jobId,
            'body' => $jobBody,
        ];
        $expected = [
            'id' => "{$host}.{$jobId}",
            'body' => $jobBody,
        ];
        $connection = $this->createMockConnection($host);

        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);

        static::assertEquals($expected, $this->pool->peekBuried());
    }

    public function testPeekBuriedWithNoBuriedJobs()
    {
        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->willThrowException(new RuntimeException());
        static::assertFalse($this->pool->peekBuried());
    }

    public function testStats()
    {
        $noOfServers = 3;
        $ready = 2;
        $other = 8;
        $response = [
            'current-jobs-ready' => $ready,
            'some-other' => $other,
        ];
        $this->collection->expects(static::any())
            ->method('sendToAll')
            ->willReturnCallback(function ($command, $arguments, $success, $failure) use ($response, $noOfServers) {
                for ($i = 0; $i < $noOfServers; $i++) {
                    call_user_func($success, [
                        'connection' => null,
                        'response' => $response,
                    ]);
                }
            });
        static::assertEquals(
            [
                'current-jobs-ready' => ($ready * $noOfServers),
                'some-other' => ($other * $noOfServers),
            ],
            $this->pool->stats()
        );
    }

    public function testStatsJob()
    {
        $host = 'host:123';
        $jobId = '123';
        $hostJobId = "{$host}.{$jobId}";
        $jobBody = 'jobBody';
        $connection = $this->createMockConnection($host);
        $response = [
            'id' => $jobId,
            'body' => $jobBody,
        ];
        $expected = [
            'id' => $hostJobId,
            'body' => $jobBody,
        ];

        $this->collection->expects(static::any())
            ->method('sendToExact')
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);
        static::assertEquals($expected, $this->pool->statsJob($hostJobId));
    }

    public function testStatsTube()
    {
        $noOfServers = 3;
        $ready = 2;
        $other = 8;
        $response = [
            'current-jobs-ready' => $ready,
            'some-other' => $other,
        ];
        $this->collection->expects(static::any())
            ->method('sendToAll')
            ->willReturnCallback(function ($command, $arguments, $success, $failure) use ($response, $noOfServers) {
                for ($i = 0; $i < $noOfServers; $i++) {
                    call_user_func($success, [
                        'connection' => null,
                        'response' => $response,
                    ]);
                }
            });
        static::assertEquals(
            [
                'current-jobs-ready' => ($ready * $noOfServers),
                'some-other' => ($other * $noOfServers),
            ],
            $this->pool->statsTube('test-tube')
        );
    }

    /**
     * @param integer $kickAmount
     * @param integer $expected
     * @dataProvider kickDataProvider
     */
    public function testKick(array $kickValues, $kickAmount, $expected)
    {
        $connection = $this->createMockConnection('host:123');
        $at = 0;
        foreach ($kickValues as $index => $kickValue) {
            if ($kickValue == 0) {
                continue;
            }
            $connection->expects(static::at($at++))
                ->method('kick')
                ->willReturnCallback(function ($quantity) use ($kickValue) {
                    return $quantity < $kickValue ? $quantity : $kickValue;
                });
        }

        $this->collection->expects(static::any())
            ->method('sendToAll')
            ->willReturnCallback(function ($command, $arguments, $success, $failure) use ($kickValues, $connection) {
                foreach ($kickValues as $count) {
                    $response = [
                        'current-jobs-buried' => $count,
                    ];
                    call_user_func($success, [
                        'connection' => $connection,
                        'response' => $response,
                    ]);
                }
            });

        static::assertEquals($expected, $this->pool->kick($kickAmount));
    }

    public function kickDataProvider()
    {
        return [
            [[1, 2, 4], 100, 7],
            [[1, 0, 4], 100, 5],
            [[0, 0, 0], 100, 0],
            [[33, 33], 100, 66],
            [[33, 33, 33], 100, 99],
            [[40, 40, 40], 100, 100],
        ];
    }

    public function testListTubes()
    {
        $expected = ['test1', 'test2', 'test3', 'test4'];

        $this->collection->expects(static::any())
            ->method('sendToAll')
            ->willReturnCallback(function ($command, $args, $success, $failure) use ($expected) {
                $success([
                    'connection' => null,
                    'response' => array_slice($expected, 0, 2),
                ]);
                $success([
                    'connection' => null,
                    'response' => array_slice($expected, 2, 1),
                ]);
                $success([
                    'connection' => null,
                    'response' => array_slice($expected, 2, 2),
                ]);
            });

        $actual = $this->pool->listTubes();
        sort($actual); // this is so they match
        static::assertEquals($expected, $actual);
    }

    public function testListTubeUsed()
    {
        $tube = 'test-tube';
        $this->pool->useTube($tube);
        static::assertSame($tube, $this->pool->listTubeUsed());
    }

    public function testListTubesWatchDefaultState()
    {
        static::assertEquals(['default'], $this->pool->listTubesWatched());
    }

    public function testListTubesWatched()
    {
        $this->pool->watch('test');
        static::assertEquals(['default', 'test'], $this->pool->listTubesWatched());
    }

    public function testCombineIdIsNotTheJobId()
    {
        $jobId = 123;
        $connection = $this->createMockConnection('host');
        static::assertNotEquals($jobId, $this->pool->combineId($connection, $jobId));
    }

    public function testCombineIdContainsJob()
    {
        $jobId = 123;
        $connection = $this->createMockConnection('host');
        static::assertContains((string)$jobId, $this->pool->combineId($connection, $jobId));
    }

    public function testCombineAndSplitReturnCorrectJob()
    {
        $jobId = 234;
        $connection = $this->createMockConnection('127.0.0.1');

        $poolId = $this->pool->combineId($connection, $jobId);
        [$actualHost, $actualJobId] = $this->pool->splitId($poolId);
        static::assertEquals($jobId, $actualJobId);
    }

    public function testCombineAndSplitReturnCorrectHost()
    {
        $host = '127.0.0.1';
        $connection = $this->createMockConnection($host);

        $poolId = $this->pool->combineId($connection, 123);
        [$actualHost, ] = $this->pool->splitId($poolId);
        static::assertEquals($host, $actualHost);
    }

    /**
     * @param string $host
     * @return Connection|MockObject
     */
    protected function createMockConnection($host)
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::any())
            ->method('getName')
            ->willReturn($host);
        return $connection;
    }
}
