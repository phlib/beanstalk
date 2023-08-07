<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Pool\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    private Pool $pool;

    /**
     * @var Collection|MockObject
     */
    private Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collection = $this->createMock(Collection::class);
        $this->pool = new Pool($this->collection);
    }

    public function testDisconnectCallsAllConnections(): void
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
     * @dataProvider disconnectReturnsValueDataProvider
     */
    public function testDisconnectReturnsValue(bool $expected, array $returnValues): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::any())
            ->method('disconnect')
            ->willReturnOnConsecutiveCalls(...$returnValues);
        $collection = new \ArrayIterator([$connection, $connection]);
        $this->collection->expects(static::any())
            ->method('getIterator')
            ->willReturn($collection);
        static::assertSame($expected, $this->pool->disconnect());
    }

    public function disconnectReturnsValueDataProvider(): array
    {
        return [
            [true, [true, true]],
            [false, [false, true]],
            [false, [true, false]],
            [false, [false, false]],
        ];
    }

    public function testUseTubeCallsAllConnections(): void
    {
        $tube = 'test-tube';
        $this->collection->expects(static::once())
            ->method('sendToAll', [])
            ->with('useTube', [$tube]);
        $this->pool->useTube($tube);
    }

    public function testIgnoreDoesNotAllowLessThanOneWatching(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Cannot ignore the only tube in the watch list');

        // 'default' tube is already being watched
        $this->pool->ignore('default');
    }

    public function testIgnore(): void
    {
        $actual = $this->pool->watch('test-tube');
        static::assertSame(2, $actual);
        static::assertSame(1, $this->pool->ignore('default'));
    }

    public function testPutSuccess(): void
    {
        $jobId = rand();
        $connectionName = '127.0.0.1:11300';

        $connection = $this->createMock(Connection::class);
        $connection->method('getName')
            ->willReturn($connectionName);
        $this->collection->expects(static::once())
            ->method('sendToOne')
            ->with('put', ['myJobData'])
            ->willReturn([
                'connection' => $connection,
                'response' => $jobId,
            ]);

        $combinedId = $this->pool->put('myJobData');

        $expectedId = $connectionName . '.' . $jobId;
        static::assertSame($expectedId, $combinedId);
    }

    public function testPutTotalFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->with('put', ['myJobData'])
            ->willThrowException(new RuntimeException());
        $this->pool->put('myJobData');
    }

    /**
     * @medium
     */
    public function testReserveWithNoJobsDoesNotTakeLongerThanTimeout(): void
    {
        if ((bool)getenv('TEST_SKIP_TIMING') === true) {
            static::markTestSkipped('Timing test skipped');
        }

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::RESERVE_NO_JOBS_AVAILABLE_MSG);
        $this->expectExceptionCode(NotFoundException::RESERVE_NO_JOBS_AVAILABLE_CODE);

        $this->collection->expects(static::any())
            ->method('getAvailableKeys')
            ->willReturn(['host:123', 'host:456']);
        $this->collection->expects(static::any())
            ->method('sendToExact')
            ->with(static::anything(), 'reserve', [0])
            ->willThrowException(new NotFoundException(
                NotFoundException::RESERVE_NO_JOBS_AVAILABLE_MSG,
                NotFoundException::RESERVE_NO_JOBS_AVAILABLE_CODE,
            ));
        $startTime = time();
        $this->pool->reserve(2);
        $totalTime = time() - $startTime;
        static::assertGreaterThanOrEqual(2, $totalTime);
        static::assertLessThanOrEqual(3, $totalTime);
    }

    public function testReserve(): void
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
            ->with(static::anything(), 'reserve', [0])
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);
        static::assertSame($expected, $this->pool->reserve());
    }

    public function testReserveWithNoJobsOnFirstServer(): void
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
        $this->collection->expects(static::exactly(2))
            ->method('sendToExact')
            ->with(static::anything(), 'reserve', [0])
            ->willReturnCallback(function () use ($connection, $response) {
                static $count = 0;
                if ($count++ === 0) {
                    throw new NotFoundException(
                        NotFoundException::RESERVE_NO_JOBS_AVAILABLE_MSG,
                        NotFoundException::RESERVE_NO_JOBS_AVAILABLE_CODE,
                    );
                }
                return [
                    'connection' => $connection,
                    'response' => $response,
                ];
            });
        static::assertSame($expected, $this->pool->reserve());
    }

    public function testReserveWithFailingServer(): void
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
        $invocationRule = static::exactly(2);
        $result = [
            'connection' => $connection,
            'response' => $response,
        ];
        $this->collection->expects($invocationRule)
            ->method('sendToExact')
            ->with(static::anything(), 'reserve', [0])
            ->willReturnCallback(function () use ($invocationRule, $result): array {
                switch ($invocationRule->getInvocationCount()) {
                    case 1:
                        throw new RuntimeException();
                    case 2:
                        return $result;
                    default:
                        throw new \InvalidArgumentException('Unexpected invocation');
                }
            });
        static::assertSame($expected, $this->pool->reserve());
    }

    public function testPoolIdWithInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->pool->release('123');
    }

    /**
     * @dataProvider methodsWithJobIdDataProvider
     */
    public function testMethodsWithJobId(string $method): void
    {
        $host = 'host:456';
        $jobId = 123;
        $this->collection->expects(static::once())
            ->method('sendToExact')
            ->with(
                static::equalTo($host),
                static::equalTo($method),
                static::containsIdentical($jobId)
            );
        $this->pool->{$method}("{$host}.{$jobId}");
    }

    public function methodsWithJobIdDataProvider(): array
    {
        return [['delete'], ['release'], ['bury'], ['touch']];
    }

    public function testPeek(): void
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
            ->with(static::anything(), 'peek', [$jobId])
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);

        static::assertSame($expected, $this->pool->peek("{$host}.{$jobId}"));
    }

    public function testPeekReady(): void
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
            ->with('peekReady', [])
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);

        static::assertSame($expected, $this->pool->peekReady());
    }

    public function testPeekReadyWithNoReadyJobs(): void
    {
        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->with('peekReady', [])
            ->willReturn([
                'connection' => null,
                'response' => null,
            ]);
        static::assertNull($this->pool->peekReady());
    }

    public function testPeekDelayed(): void
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
            ->with('peekDelayed', [])
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);

        static::assertSame($expected, $this->pool->peekDelayed());
    }

    public function testPeekDelayedWithNoDelayedJobs(): void
    {
        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->with('peekDelayed', [])
            ->willReturn([
                'connection' => null,
                'response' => null,
            ]);
        static::assertNull($this->pool->peekDelayed());
    }

    public function testPeekBuried(): void
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
            ->with('peekBuried', [])
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);

        static::assertSame($expected, $this->pool->peekBuried());
    }

    public function testPeekBuriedWithNoBuriedJobs(): void
    {
        $this->collection->expects(static::any())
            ->method('sendToOne')
            ->with('peekBuried', [])
            ->willThrowException(new RuntimeException());
        static::assertNull($this->pool->peekBuried());
    }

    public function testStats(): void
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
            ->with('stats', [])
            ->willReturnCallback(function ($command, $arguments, $success, $failure) use ($response, $noOfServers) {
                for ($i = 0; $i < $noOfServers; $i++) {
                    call_user_func($success, [
                        'connection' => null,
                        'response' => $response,
                    ]);
                }
            });
        static::assertSame(
            [
                'current-jobs-ready' => ($ready * $noOfServers),
                'some-other' => ($other * $noOfServers),
            ],
            $this->pool->stats()
        );
    }

    public function testStatsJob(): void
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
            ->with(static::anything(), 'statsJob', [$jobId])
            ->willReturn([
                'connection' => $connection,
                'response' => $response,
            ]);
        static::assertSame($expected, $this->pool->statsJob($hostJobId));
    }

    public function testStatsTube(): void
    {
        $tube = 'test-tube';
        $noOfServers = 3;
        $ready = 2;
        $other = 8;
        $response = [
            'current-jobs-ready' => $ready,
            'some-other' => $other,
        ];
        $this->collection->expects(static::any())
            ->method('sendToAll')
            ->with('statsTube', [$tube])
            ->willReturnCallback(function ($command, $arguments, $success, $failure) use ($response, $noOfServers) {
                for ($i = 0; $i < $noOfServers; $i++) {
                    call_user_func($success, [
                        'connection' => null,
                        'response' => $response,
                    ]);
                }
            });
        static::assertSame(
            [
                'current-jobs-ready' => ($ready * $noOfServers),
                'some-other' => ($other * $noOfServers),
            ],
            $this->pool->statsTube($tube)
        );
    }

    /**
     * @dataProvider kickDataProvider
     */
    public function testKick(array $kickValues, int $kickAmount, int $expected): void
    {
        $connection = $this->createMockConnection('host:123');

        $nonZeroKicks = array_values(array_filter($kickValues));
        $invocationRule = static::exactly(count($nonZeroKicks));
        $connection->expects($invocationRule)
            ->method('kick')
            ->willReturnCallback(function ($quantity) use ($invocationRule, $nonZeroKicks) {
                $index = $invocationRule->getInvocationCount() - 1;
                if (!isset($nonZeroKicks[$index])) {
                    throw new \InvalidArgumentException('Unexpected invocation');
                }
                $kickValue = $nonZeroKicks[$index];
                return $quantity < $kickValue ? $quantity : $kickValue;
            });

        $this->collection->expects(static::any())
            ->method('sendToAll')
            ->with('statsTube')
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

        static::assertSame($expected, $this->pool->kick($kickAmount));
    }

    public function kickDataProvider(): array
    {
        return [
            'moreThanBuried' => [[1, 2, 4], 100, 7],
            'moreThanBuriedWithZero' => [[1, 0, 4], 100, 5],
            'zeroBuried' => [[0, 0, 0], 100, 0],
            'moreBuriedThanKicked' => [[40, 40, 40], 100, 100],
            'moreBuriedInFirstThanKicked' => [[120, 40, 40], 100, 100],
        ];
    }

    public function testListTubes(): void
    {
        $expected = ['test1', 'test2', 'test3', 'test4'];

        $this->collection->expects(static::any())
            ->method('sendToAll')
            ->with('listTubes', [])
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
        static::assertSame($expected, $actual);
    }

    public function testListTubeUsed(): void
    {
        $tube = 'test-tube';
        $this->pool->useTube($tube);
        static::assertSame($tube, $this->pool->listTubeUsed());
    }

    public function testListTubesWatchDefaultState(): void
    {
        static::assertSame(['default'], $this->pool->listTubesWatched());
    }

    public function testListTubesWatched(): void
    {
        $actual = $this->pool->watch('test');
        static::assertSame(2, $actual);
        static::assertSame(['default', 'test'], $this->pool->listTubesWatched());
    }

    /**
     * @return Connection|MockObject
     */
    private function createMockConnection(string $host): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::any())
            ->method('getName')
            ->willReturn($host);
        return $connection;
    }
}
