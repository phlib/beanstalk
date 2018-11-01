<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    /**
     * @var Socket|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $socket;

    /**
     * @var Connection
     */
    protected $beanstalk;

    public function setUp()
    {
        $this->socket = $this->createMock(Socket::class);
        $this->beanstalk = new Connection('hostname');
        $this->beanstalk->setSocket($this->socket);
        parent::setUp();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(\Phlib\Beanstalk\ConnectionInterface::class, $this->beanstalk);
    }

    public function testSocketIsSetCorrectly(): void
    {
        $this->assertEquals($this->socket, $this->beanstalk->getSocket());
    }

    public function testDefaultSocketImplementation(): void
    {
        $this->assertInstanceOf(Socket::class, $this->beanstalk->getSocket());
    }

    public function testDisconnectCallsSocket(): void
    {
        $this->socket->expects($this->once())
            ->method('disconnect')
            ->willReturn(true);
        $this->beanstalk->disconnect();
    }

    public function testDisconnectReturnsValue(): void
    {
        $this->socket->expects($this->any())
            ->method('disconnect')
            ->willReturn(true);
        $this->assertTrue($this->beanstalk->disconnect());
    }

    public function testPut(): void
    {
        $this->socket->expects($this->atLeastOnce())
            ->method('read')
            ->willReturn('INSERTED 123');
        $this->beanstalk->put('foo-bar');
    }

    public function testReserve(): void
    {
        $this->socket->expects($this->atLeastOnce())
            ->method('read')
            ->willReturn('RESERVED 123 456');
        $this->beanstalk->reserve();
    }

    public function testReserveDecodesData(): void
    {
        $expectedData = ['foo' => 'bar' , 'bar' => 'baz'];
        $expectedData = @(string)$expectedData;
        $this->socket->expects($this->atLeastOnce())
            ->method('read')
            ->will($this->onConsecutiveCalls('RESERVED 123 456', "Array\r\n"));
        $jobData = $this->beanstalk->reserve();
        $this->assertEquals($expectedData, $jobData['body']);
    }

    public function testDelete(): void
    {
        $id = 234;
        $this->execute("delete $id", 'DELETED', 'delete', [$id]);
    }

    public function testRelease(): void
    {
        $id = 234;
        $this->execute("release $id", 'RELEASED', 'release', [$id]);
    }

    public function testUseTube(): void
    {
        $tube = 'test-tube';
        $this->execute("use $tube", "USING $tube", 'useTube', [$tube]);
    }

    public function testBury(): void
    {
        $id = 534;
        $this->execute("bury $id", 'BURIED', 'bury', [$id]);
    }

    public function testTouch(): void
    {
        $id = 567;
        $this->execute("touch $id", 'TOUCHED', 'touch', [$id]);
    }

    public function testWatch(): void
    {
        $tube = 'test-tube';
        $this->execute("watch $tube", "WATCHING $tube", 'watch', [$tube]);
    }

    public function testWatchForExistingWatchedTube(): void
    {
        $tube = 'test-tube';
        $this->execute("watch $tube", "WATCHING 123", 'watch', [$tube]);
        $this->beanstalk->watch($tube);
    }

    public function testIgnore(): void
    {
        $tube = 'test-tube';
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('WATCHING 123');
        $this->beanstalk->watch($tube);
        $this->execute("ignore $tube", 'WATCHING 123', 'ignore', [$tube]);
    }

    public function testIgnoreDoesNothingWhenNotWatching(): void
    {
        $tube = 'test-tube';
        $this->socket->expects($this->never())
            ->method('write');
        $this->beanstalk->ignore($tube);
    }

    public function testIgnoreDoesNothingWhenOnlyHasOneTube(): void
    {
        $this->assertFalse($this->beanstalk->ignore('default'));
    }

    public function testPeek(): void
    {
        $id = 245;
        $this->execute("peek $id", ["FOUND $id 678", '{"foo":"bar","bar":"baz"}'], 'peek', [$id]);
    }

    public function testPeekReady(): void
    {
        $this->execute("peek-ready", ["FOUND 234 678", '{"foo":"bar","bar":"baz"}'], 'peekReady');
    }

    public function testPeekDelayed(): void
    {
        $this->execute("peek-delayed", ["FOUND 234 678", '{"foo":"bar","bar":"baz"}'], 'peekDelayed');
    }

    public function testPeekBuried(): void
    {
        $this->execute("peek-buried", ["FOUND 234 678", '{"foo":"bar","bar":"baz"}'], 'peekBuried');
    }

    public function testPeekNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $id = 245;
        $this->assertFalse($this->execute("peek $id", 'NOT_FOUND', 'peek', [$id]));
    }

    public function testPeekReadyNotFound(): void
    {
        $this->assertFalse($this->execute("peek-ready", 'NOT_FOUND', 'peekReady'));
    }

    public function testPeekDelayedNotFound(): void
    {
        $this->assertFalse($this->execute("peek-delayed", 'NOT_FOUND', 'peekDelayed'));
    }

    public function testPeekBuriedNotFound(): void
    {
        $this->assertFalse($this->execute("peek-buried", 'NOT_FOUND', 'peekBuried'));
    }

    public function testKick(): void
    {
        $bound = 123;
        $this->execute("kick $bound", "KICKED $bound", 'kick', [$bound]);
    }

    public function testKickEmpty(): void
    {
        $bound = 1;
        $quantity = $this->execute("kick $bound", "KICKED 0", 'kick', [$bound]);
        $this->assertEquals(0, $quantity);
    }

    public function testDefaultListOfTubesWatched(): void
    {
        $expected = ['default'];
        $this->assertEquals($expected, $this->beanstalk->listTubesWatched());
    }

    public function testDefaultTubeUsed(): void
    {
        $this->assertEquals('default', $this->beanstalk->listTubeUsed());
    }

    public function testStats(): void
    {
        $yaml  = 'key1: value1';
        $stats = ['key1' => 'value1'];

        $actual = $this->execute('stats', ["OK 1234\r\n", "---\n$yaml\r\n"], 'stats');
        $this->assertEquals($stats, $actual);
    }

    public function testStatsJob(): void
    {
        $yaml  = 'key1: value1';
        $stats = ['key1' => 'value1'];

        $actual = $this->execute('stats-job', ["OK 1234\r\n", "---\n$yaml\r\n"], 'statsJob', [123]);
        $this->assertEquals($stats, $actual);
    }

    public function testStatsTube(): void
    {
        $yaml  = 'key1: value1';
        $stats = ['key1' => 'value1'];

        $actual = $this->execute('stats-tube', ["OK 1234\r\n", "---\n$yaml\r\n"], 'statsTube', ['test-tube']);
        $this->assertEquals($stats, $actual);
    }

    protected function execute($command, $response, $method, array $arguments = [])
    {
        $this->socket->expects($this->once())
            ->method('write')
            ->with($this->stringContains($command));
        if (\is_array($response)) {
            $thisReturn = call_user_func_array([$this, 'onConsecutiveCalls'], $response);
            $this->socket->expects($this->any())
                ->method('read')
                ->will($thisReturn);
        } else {
            $this->socket->expects($this->any())
                ->method('read')
                ->willReturn($response);
        }
        return \call_user_func_array([$this->beanstalk, $method], $arguments);
    }
}
