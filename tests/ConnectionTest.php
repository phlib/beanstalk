<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    /**
     * @var Socket|MockObject
     */
    private Socket $socket;

    private Connection $beanstalk;

    protected function setUp(): void
    {
        $this->socket = $this->createMock(Socket::class);
        $this->beanstalk = new Connection(
            'hostname',
            Socket::DEFAULT_PORT,
            [],
            fn () => $this->socket,
        );
        parent::setUp();
    }

    public function testImplementsInterface(): void
    {
        static::assertInstanceOf(ConnectionInterface::class, $this->beanstalk);
    }

    public function testGetName(): void
    {
        $hostname = sha1(uniqid('hostname'));
        $port = rand(10000, 15000);

        $connection = new Connection(
            $hostname,
            $port,
            [],
            fn () => $this->socket,
        );

        $expected = $hostname . ':' . $port;
        static::assertSame($expected, $connection->getName());
    }

    public function testDisconnectCallsSocket(): void
    {
        $this->socket->expects(static::once())
            ->method('disconnect')
            ->willReturn(true);
        $this->beanstalk->disconnect();
    }

    public function testDisconnectReturnsValue(): void
    {
        $this->socket->expects(static::any())
            ->method('disconnect')
            ->willReturn(true);
        static::assertTrue($this->beanstalk->disconnect());
    }

    /**
     * @dataProvider dataFilterId
     * @param mixed $id
     */
    public function testFilterId(string $command, $id): void
    {
        // No need to test valid values, as the Command classes only accept strict integers
        // Only need to verify that unexpected values are rejected
        $this->expectException(InvalidArgumentException::class);

        $this->beanstalk->{$command}($id);
    }

    public function dataFilterId(): iterable
    {
        $dataTypes = [
            'array' => [123],
            'object' => (object)[123],
            'cast-mismatch' => '123abc',
        ];
        $commands = [
            'touch',
            'release',
            'bury',
            'delete',
            'peek',
            'statsJob',
        ];
        foreach ($dataTypes as $name => $value) {
            foreach ($commands as $command) {
                yield $command . '-' . $name => [$command, $value];
            }
        }
    }

    public function testPut(): void
    {
        $this->socket->expects(static::atLeastOnce())
            ->method('read')
            ->willReturn('INSERTED 123');
        $this->beanstalk->put('foo-bar');
    }

    public function testReserve(): void
    {
        $this->socket->expects(static::atLeastOnce())
            ->method('read')
            ->willReturn('RESERVED 123 456');
        $this->beanstalk->reserve();
    }

    public function testReserveDecodesData(): void
    {
        $expectedData = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];
        $expectedData = @(string)$expectedData;
        $this->socket->expects(static::atLeastOnce())
            ->method('read')
            ->willReturnOnConsecutiveCalls('RESERVED 123 456', "Array\r\n");
        $jobData = $this->beanstalk->reserve();
        static::assertSame($expectedData, $jobData['body']);
    }

    public function testDelete(): void
    {
        $id = 234;
        $this->execute("delete {$id}", 'DELETED', 'delete', [$id]);
    }

    public function testRelease(): void
    {
        $id = 234;
        $this->execute("release {$id}", 'RELEASED', 'release', [$id]);
    }

    public function testUseTube(): void
    {
        $tube = 'test-tube';
        $this->execute("use {$tube}", "USING {$tube}", 'useTube', [$tube]);
    }

    public function testBury(): void
    {
        $id = 534;
        $this->execute("bury {$id}", 'BURIED', 'bury', [$id]);
    }

    public function testTouch(): void
    {
        $id = 567;
        $this->execute("touch {$id}", 'TOUCHED', 'touch', [$id]);
    }

    public function testWatch(): void
    {
        $tube = 'test-tube';
        $actual = $this->execute("watch {$tube}", 'WATCHING 2', 'watch', [$tube]);
        static::assertSame(2, $actual);
    }

    public function testWatchForExistingWatchedTube(): void
    {
        $tube = 'test-tube';

        $actual1 = $this->execute("watch {$tube}", 'WATCHING 123', 'watch', [$tube]);
        static::assertSame(2, $actual1);

        $actual2 = $this->beanstalk->watch($tube);
        static::assertSame(2, $actual2);
    }

    public function testIgnore(): void
    {
        $tube = 'test-tube';
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('WATCHING 123');
        $before = $this->beanstalk->watch($tube);
        static::assertSame(2, $before);

        $after = $this->execute("ignore {$tube}", 'WATCHING 123', 'ignore', [$tube]);
        static::assertSame(1, $after);
    }

    public function testIgnoreDoesNothingWhenNotWatching(): void
    {
        $tube = 'test-tube';
        $this->socket->expects(static::never())
            ->method('write');
        $actual = $this->beanstalk->ignore($tube);
        static::assertSame(1, $actual);
    }

    public function testIgnoreExceptionWhenOnlyHasOneTube(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Cannot ignore the only tube in the watch list');

        $this->beanstalk->ignore('default');
    }

    public function testPeek(): void
    {
        $id = 245;
        $this->execute("peek {$id}", ["FOUND {$id} 678", '{"foo":"bar","bar":"baz"}'], 'peek', [$id]);
    }

    public function testPeekReady(): void
    {
        $this->execute('peek-ready', ['FOUND 234 678', '{"foo":"bar","bar":"baz"}'], 'peekReady');
    }

    public function testPeekDelayed(): void
    {
        $this->execute('peek-delayed', ['FOUND 234 678', '{"foo":"bar","bar":"baz"}'], 'peekDelayed');
    }

    public function testPeekBuried(): void
    {
        $this->execute('peek-buried', ['FOUND 234 678', '{"foo":"bar","bar":"baz"}'], 'peekBuried');
    }

    public function testPeekNotFound(): void
    {
        $jobId = rand();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(sprintf(NotFoundException::JOB_ID_MSG_F, $jobId));
        $this->expectExceptionCode(NotFoundException::JOB_ID_CODE);

        $this->execute("peek {$jobId}", 'NOT_FOUND', 'peek', [$jobId]);
    }

    public function testPeekReadyNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::PEEK_STATUS_MSG);
        $this->expectExceptionCode(NotFoundException::PEEK_STATUS_CODE);

        $this->execute('peek-ready', 'NOT_FOUND', 'peekReady');
    }

    public function testPeekDelayedNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::PEEK_STATUS_MSG);
        $this->expectExceptionCode(NotFoundException::PEEK_STATUS_CODE);

        $this->execute('peek-delayed', 'NOT_FOUND', 'peekDelayed');
    }

    public function testPeekBuriedNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::PEEK_STATUS_MSG);
        $this->expectExceptionCode(NotFoundException::PEEK_STATUS_CODE);

        $this->execute('peek-buried', 'NOT_FOUND', 'peekBuried');
    }

    public function testKick(): void
    {
        $bound = 123;
        $this->execute("kick {$bound}", "KICKED {$bound}", 'kick', [$bound]);
    }

    public function testKickEmpty(): void
    {
        $bound = 1;
        $quantity = $this->execute("kick {$bound}", 'KICKED 0', 'kick', [$bound]);
        static::assertSame(0, $quantity);
    }

    public function testDefaultListOfTubesWatched(): void
    {
        $expected = ['default'];
        static::assertSame($expected, $this->beanstalk->listTubesWatched());
    }

    public function testDefaultTubeUsed(): void
    {
        static::assertSame('default', $this->beanstalk->listTubeUsed());
    }

    public function testStats(): void
    {
        $yaml = 'key1: value1';
        $stats = [
            'key1' => 'value1',
        ];

        $actual = $this->execute('stats', ["OK 1234\r\n", "---\n{$yaml}\r\n"], 'stats');
        static::assertSame($stats, $actual);
    }

    public function testStatsJob(): void
    {
        $yaml = 'key1: value1';
        $stats = [
            'key1' => 'value1',
        ];

        $actual = $this->execute('stats-job', ["OK 1234\r\n", "---\n{$yaml}\r\n"], 'statsJob', [123]);
        static::assertSame($stats, $actual);
    }

    public function testStatsTube(): void
    {
        $yaml = 'key1: value1';
        $stats = [
            'key1' => 'value1',
        ];

        $actual = $this->execute('stats-tube', ["OK 1234\r\n", "---\n{$yaml}\r\n"], 'statsTube', ['test-tube']);
        static::assertSame($stats, $actual);
    }

    /**
     * @param mixed $response
     * @return mixed
     */
    private function execute(string $command, $response, string $method, array $arguments = [])
    {
        $this->socket->expects(static::once())
            ->method('write')
            ->with(static::stringContains($command));
        if (is_array($response)) {
            $this->socket->expects(static::any())
                ->method('read')
                ->willReturnOnConsecutiveCalls(...$response);
        } else {
            $this->socket->expects(static::any())
                ->method('read')
                ->willReturn($response);
        }
        return $this->beanstalk->{$method}(...$arguments);
    }
}
