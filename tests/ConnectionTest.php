<?php

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Connection\Socket;
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

    protected function setUp()
    {
        $this->socket = $this->getMockBuilder(Socket::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->beanstalk = new Connection($this->socket);
        parent::setUp();
    }

    public function testImplementsInterface()
    {
        static::assertInstanceOf(ConnectionInterface::class, $this->beanstalk);
    }

    public function testSocketIsSetCorrectly()
    {
        static::assertEquals($this->socket, $this->beanstalk->getSocket());
    }

    public function testDefaultSocketImplementation()
    {
        static::assertInstanceOf(Socket::class, $this->beanstalk->getSocket());
    }

    public function testDisconnectCallsSocket()
    {
        $this->socket->expects(static::once())
            ->method('disconnect')
            ->willReturn(true);
        $this->beanstalk->disconnect();
    }

    public function testDisconnectReturnsValue()
    {
        $this->socket->expects(static::any())
            ->method('disconnect')
            ->willReturn(true);
        static::assertTrue($this->beanstalk->disconnect());
    }

    public function testPut()
    {
        $this->socket->expects(static::atLeastOnce())
            ->method('read')
            ->willReturn('INSERTED 123');
        $this->beanstalk->put('foo-bar');
    }

    public function testReserve()
    {
        $this->socket->expects(static::atLeastOnce())
            ->method('read')
            ->willReturn('RESERVED 123 456');
        $this->beanstalk->reserve();
    }

    public function testReserveDecodesData()
    {
        $expectedData = ['foo' => 'bar' , 'bar' => 'baz'];
        $expectedData = @(string)$expectedData;
        $this->socket->expects(static::atLeastOnce())
            ->method('read')
            ->willReturnOnConsecutiveCalls('RESERVED 123 456', "Array\r\n");
        $jobData = $this->beanstalk->reserve();
        static::assertEquals($expectedData, $jobData['body']);
    }

    public function testDelete()
    {
        $id = 234;
        $this->execute("delete $id", 'DELETED', 'delete', [$id]);
    }

    public function testRelease()
    {
        $id = 234;
        $this->execute("release $id", 'RELEASED', 'release', [$id]);
    }

    public function testUseTube()
    {
        $tube = 'test-tube';
        $this->execute("use $tube", 'USING', 'useTube', [$tube]);
    }

    public function testBury()
    {
        $id = 534;
        $this->execute("bury $id", 'BURIED', 'bury', [$id]);
    }

    public function testTouch()
    {
        $id = 567;
        $this->execute("touch $id", 'TOUCHED', 'touch', [$id]);
    }

    public function testWatch()
    {
        $tube = 'test-tube';
        $this->execute("watch $tube", "WATCHING $tube", 'watch', [$tube]);
    }

    public function testWatchForExistingWatchedTube()
    {
        $tube = 'test-tube';
        $this->execute("watch $tube", "WATCHING 123", 'watch', [$tube]);
        $this->beanstalk->watch($tube);
    }

    public function testIgnore()
    {
        $tube = 'test-tube';
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('WATCHING 123');
        $this->beanstalk->watch($tube);
        $this->execute("ignore $tube", 'WATCHING 123', 'ignore', [$tube]);
    }

    public function testIgnoreDoesNothingWhenNotWatching()
    {
        $tube = 'test-tube';
        $this->socket->expects(static::never())
            ->method('write');
        $this->beanstalk->ignore($tube);
    }

    public function testIgnoreDoesNothingWhenOnlyHasOneTube()
    {
        static::assertFalse($this->beanstalk->ignore('default'));
    }

    public function testPeek()
    {
        $id = 245;
        $this->execute("peek $id", ["FOUND $id 678", '{"foo":"bar","bar":"baz"}'], 'peek', [$id]);
    }

    public function testPeekReady()
    {
        $this->execute("peek-ready", ["FOUND 234 678", '{"foo":"bar","bar":"baz"}'], 'peekReady');
    }

    public function testPeekDelayed()
    {
        $this->execute("peek-delayed", ["FOUND 234 678", '{"foo":"bar","bar":"baz"}'], 'peekDelayed');
    }

    public function testPeekBuried()
    {
        $this->execute("peek-buried", ["FOUND 234 678", '{"foo":"bar","bar":"baz"}'], 'peekBuried');
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testPeekNotFound()
    {
        $id = 245;
        static::assertFalse($this->execute("peek $id", 'NOT_FOUND', 'peek', [$id]));
    }

    public function testPeekReadyNotFound()
    {
        static::assertFalse($this->execute("peek-ready", 'NOT_FOUND', 'peekReady'));
    }

    public function testPeekDelayedNotFound()
    {
        static::assertFalse($this->execute("peek-delayed", 'NOT_FOUND', 'peekDelayed'));
    }

    public function testPeekBuriedNotFound()
    {
        static::assertFalse($this->execute("peek-buried", 'NOT_FOUND', 'peekBuried'));
    }

    public function testKick()
    {
        $bound = 123;
        $this->execute("kick $bound", "KICKED $bound", 'kick', [$bound]);
    }

    public function testKickEmpty()
    {
        $bound = 1;
        $quantity = $this->execute("kick $bound", "KICKED 0", 'kick', [$bound]);
        static::assertEquals(0, $quantity);
    }

    public function testDefaultListOfTubesWatched()
    {
        $expected = ['default'];
        static::assertEquals($expected, $this->beanstalk->listTubesWatched());
    }

    public function testDefaultTubeUsed()
    {
        static::assertEquals('default', $this->beanstalk->listTubeUsed());
    }

    public function testStats()
    {
        $yaml  = 'key1: value1';
        $stats = ['key1' => 'value1'];

        $actual = $this->execute('stats', ["OK 1234\r\n", "---\n$yaml\r\n"], 'stats');
        static::assertEquals($stats, $actual);
    }

    public function testStatsJob()
    {
        $yaml  = 'key1: value1';
        $stats = ['key1' => 'value1'];

        $actual = $this->execute('stats-job', ["OK 1234\r\n", "---\n$yaml\r\n"], 'statsJob', [123]);
        static::assertEquals($stats, $actual);
    }

    public function testStatsTube()
    {
        $yaml  = 'key1: value1';
        $stats = ['key1' => 'value1'];

        $actual = $this->execute('stats-tube', ["OK 1234\r\n", "---\n$yaml\r\n"], 'statsTube', ['test-tube']);
        static::assertEquals($stats, $actual);
    }

    protected function execute($command, $response, $method, array $arguments = [])
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
        return call_user_func_array([$this->beanstalk, $method], $arguments);
    }
}
