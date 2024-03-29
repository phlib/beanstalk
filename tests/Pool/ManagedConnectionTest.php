<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Pool;

use ColinODell\PsrTestLogger\TestLogger;
use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\RuntimeException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class ManagedConnectionTest extends TestCase
{
    use PHPMock;

    private const COMMAND_PASS_THROUGH = [
        'useTube' => ['tube', 'void'],
        'put' => ['data', 123],
        'reserve' => [123, ['some result']],
        'touch' => [123, 'void'],
        'release' => [123, 'void'],
        'bury' => [123, 'void'],
        'delete' => [123, 'void'],
        'watch' => ['tube', 123],
        'ignore' => ['tube', 123],
        'peek' => [123, ['some result']],
        'statsJob' => [123, ['some result']],
        'peekReady' => [['some result']],
        'peekDelayed' => [['some result']],
        'peekBuried' => [['some result']],
        'kick' => [123, 123],
        'statsTube' => ['tube', ['some result']],
        'stats' => [['some result']],
        'listTubes' => [['some result']],
        'listTubeUsed' => ['some result'],
        'listTubesWatched' => [['some result']],
    ];

    private string $connectionName;

    /**
     * @var ConnectionInterface|MockObject
     */
    private ConnectionInterface $connection;

    private MockObject $time;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionName = sha1(uniqid('name'));
        $this->connection = $this->createMock(ConnectionInterface::class);
        $this->connection->method('getName')
            ->willReturn($this->connectionName);

        $this->time = $this->getFunctionMock(__NAMESPACE__, 'time');
    }

    public function testSetGetConnection(): void
    {
        $managed = new ManagedConnection($this->connection);
        static::assertSame($this->connection, $managed->getConnection());
    }

    public function testGetName(): void
    {
        $managed = new ManagedConnection($this->connection);
        static::assertSame($this->connectionName, $managed->getName());
    }

    public function testIsAvailableTrueByDefault(): void
    {
        $managed = new ManagedConnection($this->connection);
        static::assertTrue($managed->isAvailable());
    }

    public function testIsAvailableFalseAfterError(): void
    {
        $this->connection->expects(static::once())
            ->method('watch')
            ->willThrowException(new RuntimeException());

        $managed = new ManagedConnection($this->connection);
        try {
            $managed->watch('tube');
        } catch (RuntimeException $e) {
            // ignore
        }

        static::assertFalse($managed->isAvailable());
    }

    public function testIsAvailableBecomesAvailableAfterTime(): void
    {
        $initialTime = 100;
        $retryDelay = 600;

        $this->time->expects(static::exactly(2))
            ->willReturnOnConsecutiveCalls(
                $initialTime,
                $initialTime + $retryDelay,
            );

        $this->connection->expects(static::once())
            ->method('watch')
            ->willThrowException(new RuntimeException());

        $logger = new TestLogger();

        $managed = new ManagedConnection($this->connection, $retryDelay, $logger);
        try {
            $managed->watch('tube');
        } catch (RuntimeException $e) {
            // ignore
        }

        static::assertTrue($managed->isAvailable());

        // Expected log
        $logMsg = sprintf(
            'Connection \'%s\' failed; delay for %ds',
            $this->connectionName,
            $retryDelay,
        );
        $logCtxt = [
            'connectionName' => $this->connectionName,
            'retryDelay' => $retryDelay,
            'retryAt' => $initialTime + $retryDelay,
        ];
        static::assertCount(1, $logger->records);
        $log = $logger->records[0];
        static::assertSame(LogLevel::NOTICE, $log['level']);
        static::assertSame($logMsg, $log['message']);
        foreach ($logCtxt as $key => $expectedCtxt) {
            static::assertSame($expectedCtxt, $log['context'][$key]);
        }
    }

    public function testIsAvailableBecomesAvailableAfterSuccessfulSend(): void
    {
        $this->connection->expects(static::exactly(2))
            ->method('touch')
            ->willReturnCallback(function (): void {
                static $count = 0;
                if ($count++ === 0) {
                    throw new RuntimeException();
                }
            });

        $managed = new ManagedConnection($this->connection);
        try {
            $managed->touch(123);
        } catch (RuntimeException $e) {
            // ignore
        }

        static::assertFalse($managed->isAvailable());

        $managed->touch(123);

        static::assertTrue($managed->isAvailable());
    }

    public function testSendBubblesException(): void
    {
        $xMsg = sha1(uniqid('exception'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($xMsg);

        $command = 'stats';
        $this->connection->expects(static::any())
            ->method('stats')
            ->willThrowException(new RuntimeException($xMsg));

        $managed = new ManagedConnection($this->connection);
        $managed->stats();
    }

    /**
     * @dataProvider dataCommandPassThrough
     */
    public function testCommandPassThrough(string $command, array $mockMap): void
    {
        $mockMethod = $this->connection->expects(static::once())
            ->method($command);

        $result = array_pop($mockMap);
        if ($result !== 'void') {
            $mockMethod->willReturn($result);
        }

        $managed = new ManagedConnection($this->connection);
        $managed->{$command}(...$mockMap);
    }

    public function dataCommandPassThrough(): iterable
    {
        /**
         * Allow calls to all command methods in
         * @see \Phlib\Beanstalk\ConnectionInterface
         */
        foreach (self::COMMAND_PASS_THROUGH as $command => $map) {
            yield $command => [$command, $map];
        }
    }
}
