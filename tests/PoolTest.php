<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\RuntimeException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    use PHPMock;

    private const NAME_CONN_1 = 'connection1';

    private const NAME_CONN_2 = 'connection2';

    private Pool $pool;

    /**
     * @var Connection|MockObject
     */
    private Connection $connection1;

    /**
     * @var Connection|MockObject
     */
    private Connection $connection2;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Declare the namespaced function early, so it's available after being used in other tests
        // @see https://github.com/php-mock/php-mock-phpunit#restrictions
        PHPMock::defineFunctionMock(__NAMESPACE__, 'shuffle');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent the shuffle giving a random connection order
        $shuffle = $this->getFunctionMock(__NAMESPACE__, 'shuffle');
        $shuffle->expects(static::any())
            ->willReturn(null);

        $this->connection1 = $this->createMock(Connection::class);
        $this->connection1->method('getName')
            ->willReturn(self::NAME_CONN_1);

        $this->connection2 = $this->createMock(Connection::class);
        $this->connection2->method('getName')
            ->willReturn(self::NAME_CONN_2);

        $this->pool = new Pool([$this->connection1, $this->connection2]);
    }

    public function testConstructErrorWithNoConnections(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Connections for Pool are empty');

        new Pool([]);
    }

    public function testConstructErrorRepeatConnections(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Specified connection');
        $this->expectExceptionMessage('already exists');

        new Pool([
            $this->connection1,
            $this->connection1,
        ]);
    }

    public function testGetConnections(): void
    {
        $expected = [
            $this->connection1,
            $this->connection2,
        ];

        $actual = $this->pool->getConnections();
        self::assertSame($expected, $actual);
    }

    public function testDisconnectCallsAllConnections(): void
    {
        $this->connection1->expects(static::once())
            ->method('disconnect')
            ->willReturn(true);
        $this->connection2->expects(static::once())
            ->method('disconnect')
            ->willReturn(true);
        $this->pool->disconnect();
    }

    /**
     * @dataProvider disconnectReturnsValueDataProvider
     */
    public function testDisconnectReturnsValue(bool $expected, array $returnValues): void
    {
        $this->connection1->method('disconnect')
            ->willReturn($returnValues[0]);
        $this->connection2->method('disconnect')
            ->willReturn($returnValues[1]);
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

    public function testGetName(): void
    {
        $actual = $this->pool->getName();
        // Test the name has some standard characters. It doesn't matter what it is.
        self::assertMatchesRegularExpression('/[a-z0-9]{32}/', $actual);
    }

    public function testUseTubeCallsAllConnections(): void
    {
        $tube = 'test-tube';

        $this->connection1->expects(static::once())
            ->method('useTube')
            ->with($tube);
        $this->connection2->expects(static::once())
            ->method('useTube')
            ->with($tube);

        $this->pool->useTube($tube);
    }

    public function testUseTubeSkipsUnavailableConnections(): void
    {
        $tube = sha1(uniqid('tube'));

        // Force a connection error so ManagedConnection treats it as unavailable
        $this->connection1->expects(static::once())
            ->method('watch')
            ->willThrowException(new RuntimeException());
        $this->pool->watch($tube);

        $this->connection1->expects(static::never())
            ->method('useTube');
        $this->connection2->expects(static::once())
            ->method('useTube')
            ->with($tube);

        $this->pool->useTube($tube);
    }

    public function testUseTubeSkipsConnectionErrors(): void
    {
        $tube = sha1(uniqid('tube'));

        $this->connection1->expects(static::once())
            ->method('useTube')
            ->with($tube)
            ->willThrowException(new RuntimeException('connection error'));

        $this->connection2->expects(static::once())
            ->method('useTube')
            ->with($tube);

        $this->pool->useTube($tube);
    }

    public function testWatch(): void
    {
        $tube = sha1(uniqid('tube'));

        $this->connection1->expects(static::once())
            ->method('watch')
            ->with($tube)
            ->willReturn(2);

        $this->connection2->expects(static::once())
            ->method('watch')
            ->with($tube)
            ->willReturn(2);

        $actual = $this->pool->watch($tube);
        static::assertSame(2, $actual);
    }

    public function testWatchSkipsConnectionErrors(): void
    {
        $tube = sha1(uniqid('tube'));

        $this->connection1->expects(static::once())
            ->method('watch')
            ->with($tube)
            ->willThrowException(new RuntimeException('connection error'));

        $this->connection2->expects(static::once())
            ->method('watch')
            ->with($tube);

        $this->pool->watch($tube);
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

        $this->connection1->expects(static::once())
            ->method('ignore')
            ->with('default')
            ->willReturn(1);

        $this->connection2->expects(static::once())
            ->method('ignore')
            ->with('default')
            ->willReturn(1);

        static::assertSame(1, $this->pool->ignore('default'));
    }

    public function testIgnoreSkipsConnectionErrors(): void
    {
        $tube = sha1(uniqid('tube'));

        $this->pool->watch($tube);

        $this->connection1->expects(static::once())
            ->method('ignore')
            ->with($tube)
            ->willThrowException(new RuntimeException('connection error'));

        $this->connection2->expects(static::once())
            ->method('ignore')
            ->with($tube);

        $this->pool->ignore($tube);
    }

    public function testPutSuccess(): void
    {
        $jobId = rand();

        $this->connection1->expects(static::once())
            ->method('put')
            ->with('myJobData')
            ->willReturn($jobId);

        $combinedId = $this->pool->put('myJobData');

        $expectedId = self::NAME_CONN_1 . '.' . $jobId;
        static::assertSame($expectedId, $combinedId);
    }

    public function testPutTotalFailure(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to send command to one of the available servers in the pool');

        $this->connection1->method('put')
            ->with('myJobData')
            ->willThrowException(new RuntimeException('error1'));
        $this->connection2->method('put')
            ->with('myJobData')
            ->willThrowException(new RuntimeException('error2'));

        try {
            $this->pool->put('myJobData');
        } catch (RuntimeException $e) {
            // Test that the previous exception is set as the last connection's error
            $previous = $e->getPrevious();
            static::assertInstanceOf(RuntimeException::class, $previous);
            static::assertSame('error2', $previous->getMessage());
            throw $e;
        }
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

        $this->connection1->method('reserve')
            ->with(0)
            ->willThrowException(new NotFoundException(
                NotFoundException::RESERVE_NO_JOBS_AVAILABLE_MSG,
                NotFoundException::RESERVE_NO_JOBS_AVAILABLE_CODE,
            ));
        $this->connection2->method('reserve')
            ->with(0)
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
        $response = [
            'id' => $jobId,
            'body' => 'jobData',
        ];
        $expected = [
            'id' => self::NAME_CONN_1 . '.' . $jobId,
            'body' => 'jobData',
        ];

        $this->connection1->method('reserve')
            ->with(0)
            ->willReturn($response);

        static::assertSame($expected, $this->pool->reserve());
    }

    public function testReserveWithNoJobsOnFirstServer(): void
    {
        $jobId = '123';
        $response = [
            'id' => $jobId,
            'body' => 'jobData',
        ];
        $expected = [
            'id' => self::NAME_CONN_2 . '.' . $jobId,
            'body' => 'jobData',
        ];

        $this->connection1->method('reserve')
            ->with(0)
            ->willThrowException(new NotFoundException(
                NotFoundException::RESERVE_NO_JOBS_AVAILABLE_MSG,
                NotFoundException::RESERVE_NO_JOBS_AVAILABLE_CODE,
            ));
        $this->connection2->method('reserve')
            ->with(0)
            ->willReturn($response);

        static::assertSame($expected, $this->pool->reserve());
    }

    public function testReserveWithFailingServer(): void
    {
        $jobId = '123';
        $response = [
            'id' => $jobId,
            'body' => 'jobData',
        ];
        $expected = [
            'id' => self::NAME_CONN_2 . '.' . $jobId,
            'body' => 'jobData',
        ];

        $this->connection1->expects(static::once())
            ->method('reserve')
            ->with(0)
            ->willThrowException(new RuntimeException());
        $this->connection2->expects(static::once())
            ->method('reserve')
            ->with(0)
            ->willReturn($response);

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
        $server = self::NAME_CONN_1;
        $jobId = 123;

        $this->connection1->expects(static::once())
            ->method($method)
            ->with($jobId);

        $this->pool->{$method}("{$server}.{$jobId}");
    }

    public function methodsWithJobIdDataProvider(): array
    {
        return [['delete'], ['release'], ['bury'], ['touch']];
    }

    public function testPeek(): void
    {
        $server = self::NAME_CONN_2;
        $jobId = '123';
        $jobBody = 'jobBody';
        $response = [
            'id' => $jobId,
            'body' => $jobBody,
        ];
        $expected = [
            'id' => "{$server}.{$jobId}",
            'body' => $jobBody,
        ];

        $this->connection1->expects(static::never())
            ->method('peek');
        $this->connection2->expects(static::once())
            ->method('peek')
            ->with($jobId)
            ->willReturn($response);

        static::assertSame($expected, $this->pool->peek("{$server}.{$jobId}"));
    }

    public function testPeekInvalidConnection(): void
    {
        $server = substr(sha1(uniqid('conn')), 0, 10);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Specified connection '{$server}' is not in the pool");

        $jobId = '123';

        $this->connection1->expects(static::never())
            ->method('peek');
        $this->connection2->expects(static::never())
            ->method('peek');

        $this->pool->peek("{$server}.{$jobId}");
    }

    public function testPeekUnavailableConnection(): void
    {
        $server = self::NAME_CONN_1;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Specified connection '{$server}' is not currently available");

        // Force a connection error so ManagedConnection treats it as unavailable
        $this->connection1->expects(static::once())
            ->method('watch')
            ->willThrowException(new RuntimeException());
        $this->pool->watch('tube');

        $jobId = '123';

        $this->connection1->expects(static::never())
            ->method('peek');
        $this->connection2->expects(static::never())
            ->method('peek');

        $this->pool->peek("{$server}.{$jobId}");
    }

    public function testPeekReady(): void
    {
        $server = self::NAME_CONN_2;
        $jobId = '123';
        $jobBody = 'jobBody';
        $response = [
            'id' => $jobId,
            'body' => $jobBody,
        ];
        $expected = [
            'id' => "{$server}.{$jobId}",
            'body' => $jobBody,
        ];

        $this->connection1->expects(static::once())
            ->method('peekReady')
            ->willThrowException(new NotFoundException(
                NotFoundException::PEEK_STATUS_MSG,
                NotFoundException::PEEK_STATUS_CODE,
            ));
        $this->connection2->expects(static::once())
            ->method('peekReady')
            ->willReturn($response);

        static::assertSame($expected, $this->pool->peekReady());
    }

    public function testPeekReadyWithNoReadyJobs(): void
    {
        $this->expectException(NotFoundException::class);
        // Test that the last connection exception is the one that is thrown
        $this->expectExceptionMessage('error2');
        $this->expectExceptionCode(NotFoundException::PEEK_STATUS_CODE);

        $this->connection1->expects(static::once())
            ->method('peekReady')
            ->willThrowException(new NotFoundException(
                // Deliberate non-standard message to track which exception is returned
                'error1',
                NotFoundException::PEEK_STATUS_CODE,
            ));
        $this->connection2->expects(static::once())
            ->method('peekReady')
            ->willThrowException(new NotFoundException(
                // Deliberate non-standard message to track which exception is returned
                'error2',
                NotFoundException::PEEK_STATUS_CODE,
            ));

        $this->pool->peekReady();
    }

    public function testPeekDelayed(): void
    {
        $server = self::NAME_CONN_2;
        $jobId = '123';
        $jobBody = 'jobBody';
        $response = [
            'id' => $jobId,
            'body' => $jobBody,
        ];
        $expected = [
            'id' => "{$server}.{$jobId}",
            'body' => $jobBody,
        ];

        $this->connection1->expects(static::once())
            ->method('peekDelayed')
            ->willThrowException(new NotFoundException(
                NotFoundException::PEEK_STATUS_MSG,
                NotFoundException::PEEK_STATUS_CODE,
            ));
        $this->connection2->expects(static::once())
            ->method('peekDelayed')
            ->willReturn($response);

        static::assertSame($expected, $this->pool->peekDelayed());
    }

    public function testPeekDelayedWithNoDelayedJobs(): void
    {
        $this->expectException(NotFoundException::class);
        // Test that the last connection exception is the one that is thrown
        $this->expectExceptionMessage('error2');
        $this->expectExceptionCode(NotFoundException::PEEK_STATUS_CODE);

        $this->connection1->expects(static::once())
            ->method('peekDelayed')
            ->willThrowException(new NotFoundException(
                // Deliberate non-standard message to track which exception is returned
                'error1',
                NotFoundException::PEEK_STATUS_CODE,
            ));
        $this->connection2->expects(static::once())
            ->method('peekDelayed')
            ->willThrowException(new NotFoundException(
                // Deliberate non-standard message to track which exception is returned
                'error2',
                NotFoundException::PEEK_STATUS_CODE,
            ));

        $this->pool->peekDelayed();
    }

    public function testPeekBuried(): void
    {
        $server = self::NAME_CONN_2;
        $jobId = '123';
        $jobBody = 'jobBody';
        $response = [
            'id' => $jobId,
            'body' => $jobBody,
        ];
        $expected = [
            'id' => "{$server}.{$jobId}",
            'body' => $jobBody,
        ];

        $this->connection1->expects(static::once())
            ->method('peekBuried')
            ->willThrowException(new NotFoundException(
                NotFoundException::PEEK_STATUS_MSG,
                NotFoundException::PEEK_STATUS_CODE,
            ));
        $this->connection2->expects(static::once())
            ->method('peekBuried')
            ->willReturn($response);

        static::assertSame($expected, $this->pool->peekBuried());
    }

    public function testPeekBuriedWithNoBuriedJobs(): void
    {
        $this->expectException(NotFoundException::class);
        // Test that the last connection exception is the one that is thrown
        $this->expectExceptionMessage('error2');
        $this->expectExceptionCode(NotFoundException::PEEK_STATUS_CODE);

        $this->connection1->expects(static::once())
            ->method('peekBuried')
            ->willThrowException(new NotFoundException(
                // Deliberate non-standard message to track which exception is returned
                'error1',
                NotFoundException::PEEK_STATUS_CODE,
            ));
        $this->connection2->expects(static::once())
            ->method('peekBuried')
            ->willThrowException(new NotFoundException(
                // Deliberate non-standard message to track which exception is returned
                'error2',
                NotFoundException::PEEK_STATUS_CODE,
            ));

        $this->pool->peekBuried();
    }

    /**
     * @dataProvider kickDataProvider
     */
    public function testKick(array $kickValues, int $kickAmount, int $expected): void
    {
        $totalKicked = 0;
        foreach ($kickValues as $index => $kickValue) {
            $connection = 'connection' . ($index + 1);

            $invocationStats = static::once();
            $invocationKick = static::once();
            if ($totalKicked >= $kickAmount) {
                // Previous connections have exhausted the required number to kick, so no other calls should be made
                $invocationStats = static::never();
                $invocationKick = static::never();
            } elseif ($kickValue === 0) {
                // This connection reports it doesn't have any buried jobs, so it shouldn't be called to kick
                $invocationKick = static::never();
            }

            $this->{$connection}->expects($invocationStats)
                ->method('statsTube')
                ->willReturn(['current-jobs-buried' => $kickValue]);

            $this->{$connection}->expects($invocationKick)
                ->method('kick')
                ->willReturnCallback(function ($quantity) use ($kickValue) {
                    return min($quantity, $kickValue);
                });
            $totalKicked += $kickValue;
        }

        static::assertSame($expected, $this->pool->kick($kickAmount));
    }

    public function kickDataProvider(): array
    {
        return [
            'moreThanBuried' => [[1, 5], 100, 6],
            'moreThanBuriedWithZero' => [[0, 5], 100, 5],
            'zeroBuried' => [[0, 0], 100, 0],
            'moreBuriedThanKicked' => [[60, 60], 100, 100],
            'moreBuriedInFirstThanKicked' => [[120, 60], 100, 100],
        ];
    }

    public function testKickSkipsConnectionErrors(): void
    {
        $kickValue = 100;

        $this->connection1->expects(static::once())
            ->method('statsTube')
            ->willReturn(['current-jobs-buried' => $kickValue]);

        $this->connection1->expects(static::once())
            ->method('kick')
            ->with($kickValue)
            ->willThrowException(new RuntimeException('connection error'));

        $this->connection2->expects(static::once())
            ->method('statsTube')
            ->willReturn(['current-jobs-buried' => $kickValue]);

        $this->connection2->expects(static::once())
            ->method('kick')
            ->with($kickValue)
            ->willReturn($kickValue);

        $this->pool->kick($kickValue);
    }

    public function testKickSkipsConnectionErrorsInStats(): void
    {
        $kickValue = 100;

        $this->connection1->expects(static::once())
            ->method('statsTube')
            ->willThrowException(new RuntimeException('connection error'));

        $this->connection1->expects(static::never())
            ->method('kick');

        $this->connection2->expects(static::once())
            ->method('statsTube')
            ->willReturn(['current-jobs-buried' => $kickValue]);

        $this->connection2->expects(static::once())
            ->method('kick')
            ->with($kickValue)
            ->willReturn($kickValue);

        $this->pool->kick($kickValue);
    }

    public function testStatsJob(): void
    {
        $server = self::NAME_CONN_2;
        $jobId = '123';
        $poolJobId = "{$server}.{$jobId}";
        $jobBody = 'jobBody';
        $response = [
            'id' => $jobId,
            'body' => $jobBody,
        ];
        $expected = [
            'id' => $poolJobId,
            'body' => $jobBody,
        ];

        $this->connection2->expects(static::once())
            ->method('statsJob')
            ->with($jobId)
            ->willReturn($response);

        static::assertSame($expected, $this->pool->statsJob($poolJobId));
    }

    public function testStatsTube(): void
    {
        $tube = 'test-tube';
        $noOfServers = 2;
        $ready = 2;
        $other = 8;
        $response = [
            'current-jobs-ready' => $ready,
            'some-other' => $other,
        ];

        $this->connection1->expects(static::once())
            ->method('statsTube')
            ->willReturn($response);
        $this->connection2->expects(static::once())
            ->method('statsTube')
            ->willReturn($response);

        static::assertSame(
            [
                'current-jobs-ready' => ($ready * $noOfServers),
                'some-other' => ($other * $noOfServers),
            ],
            $this->pool->statsTube($tube)
        );
    }

    public function testStatsTubeSkipsConnectionErrors(): void
    {
        $tube = sha1(uniqid('tube'));
        $response = [
            'current-jobs-ready' => rand(),
            'some-other' => rand(),
        ];

        $this->connection1->expects(static::once())
            ->method('statsTube')
            ->with($tube)
            ->willThrowException(new RuntimeException('connection error'));

        $this->connection2->expects(static::once())
            ->method('statsTube')
            ->with($tube)
            ->willReturn($response);

        $actual = $this->pool->statsTube($tube);
        static::assertSame($response, $actual);
    }

    public function testStats(): void
    {
        $noOfServers = 2;
        $ready = 2;
        $other = 8;
        $response = [
            'current-jobs-ready' => $ready,
            'some-other' => $other,
        ];

        $this->connection1->expects(static::once())
            ->method('stats')
            ->willReturn($response);
        $this->connection2->expects(static::once())
            ->method('stats')
            ->willReturn($response);

        static::assertSame(
            [
                'current-jobs-ready' => ($ready * $noOfServers),
                'some-other' => ($other * $noOfServers),
            ],
            $this->pool->stats()
        );
    }

    public function testStatsSkipsConnectionErrors(): void
    {
        $response = [
            'current-jobs-ready' => rand(),
            'some-other' => rand(),
        ];

        $this->connection1->expects(static::once())
            ->method('stats')
            ->willThrowException(new RuntimeException('connection error'));

        $this->connection2->expects(static::once())
            ->method('stats')
            ->willReturn($response);

        $actual = $this->pool->stats();
        static::assertSame($response, $actual);
    }

    public function testListTubes(): void
    {
        $expected = ['test1', 'test2', 'test3', 'test4'];

        $this->connection1->expects(static::once())
            ->method('listTubes')
            ->willReturn(array_slice($expected, 0, 2));
        $this->connection2->expects(static::once())
            ->method('listTubes')
            ->willReturn(array_slice($expected, 1));

        $actual = $this->pool->listTubes();
        sort($actual); // this is so they match
        static::assertSame($expected, $actual);
    }

    public function testListTubesSkipsConnectionErrors(): void
    {
        $tubes = [sha1(uniqid('tube'))];

        $this->connection1->expects(static::once())
            ->method('listTubes')
            ->willThrowException(new RuntimeException('connection error'));

        $this->connection2->expects(static::once())
            ->method('listTubes')
            ->willReturn($tubes);

        $actual = $this->pool->listTubes();
        static::assertSame($tubes, $actual);
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
