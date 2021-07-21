<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Connection;

use Phlib\Beanstalk\Exception\SocketException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SocketTest extends TestCase
{
    use PHPMock;

    public function testImplementsInterface(): void
    {
        static::assertInstanceOf(SocketInterface::class, new Socket('localhost'));
    }

    public function testGetUniqueIdentifier(): void
    {
        $socket1 = new Socket('localhost', 11300);
        $socket2 = new Socket('localhost', 11301);
        static::assertNotEquals($socket1->getUniqueIdentifier(), $socket2->getUniqueIdentifier());
    }

    public function testConnectOnSuccessReturnsSelf(): void
    {
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects(static::any())->willReturn(true);
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects(static::any())->willReturn(true);

        static::assertInstanceOf(Socket::class, (new Socket('host'))->connect());
    }

    public function testConnectOnFailureThrowsError(): void
    {
        $this->expectException(SocketException::class);

        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects(static::any())->willReturnCallback(function ($host, $port, &$errNum, &$errStr, $timeout) {
            $errNum = 123;
            $errStr = 'Testing All The Things';
            return false;
        });
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects(static::any())->willReturn(true);

        (new Socket('host'))->connect();
    }

    /**
     * @doesNotPerformAssertions PHPMock assertions are not counted
     */
    public function testConnectsWithTheCorrectDetails(): void
    {
        $host = 'someHost';
        $port = 145234;
        $timeout = 432;

        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects(static::any())
            ->with(
                static::equalTo($host),
                static::equalTo($port),
                static::equalTo(null), // errNum
                static::equalTo(null), // errStr
                static::equalTo($timeout)
            )
            ->willReturn('(socket)');
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects(static::any())->willReturn(true);

        (new Socket($host, $port, [
            'timeout' => $timeout,
        ]))->connect();
    }

    public function testDisconnectWithValidConnection(): void
    {
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects(static::any())->willReturn(fopen('php://memory', 'r+'));
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects(static::any())->willReturn(true);
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fclose');
        $fsockopen->expects(static::once())->willReturn(true); // <- test here

        $socket = new Socket('host');
        $socket->connect();
        $socket->disconnect();
    }

    public function testDisconnectWithNoConnection(): void
    {
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects(static::any())->willReturn(true);
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects(static::any())->willReturn(true);
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fclose');
        $fsockopen->expects(static::never()); // <- test here

        $socket = new Socket('host');
        $socket->connect();
        $socket->disconnect();
    }

    /**
     * @doesNotPerformAssertions PHPMock assertions are not counted
     */
    public function testWriteSuccessfullyToTheConnection(): void
    {
        $data = 'Some Data';
        $dataLength = 9 + strlen(Socket::EOL);

        $fwrite = $this->getFunctionMock(__NAMESPACE__, 'fwrite');
        $fwrite->expects(static::any())
            ->with(
                static::anything(),
                static::stringEndsWith(Socket::EOL),
                static::equalTo($dataLength)
            )
            ->willReturn($dataLength);

        $this->getMockSocket(['write'])
            ->write($data);
    }

    public function testWriteThrowsExceptionOnError(): void
    {
        $this->expectException(SocketException::class);

        $fwrite = $this->getFunctionMock(__NAMESPACE__, 'fwrite');
        $fwrite->expects(static::any())
            ->willReturn(0);

        $this->getMockSocket(['write'])
            ->write('Some Data');
    }

    public function testReadSuccessfullyFromTheConnection(): void
    {
        $expectedData = 'Some Data';
        $stream_get_line = $this->getFunctionMock(__NAMESPACE__, 'stream_get_line');
        $stream_get_line->expects(static::any())->willReturn($expectedData);
        static::assertEquals($expectedData, $this->getMockSocket(['read'])->read());
    }

    public function testReadSuccessfullyWithLengthParam(): void
    {
        $expectedData = 'Some Data';

        $feof = $this->getFunctionMock(__NAMESPACE__, 'feof');
        $feof->expects(static::any())->willReturn(false);
        $fread = $this->getFunctionMock(__NAMESPACE__, 'fread');
        $fread->expects(static::any())->willReturn($expectedData);

        static::assertEquals($expectedData, $this->getMockSocket(['read'])->read(9));
    }

    public function testReadFailsWithBadData(): void
    {
        $this->expectException(SocketException::class);

        $stream_get_line = $this->getFunctionMock(__NAMESPACE__, 'stream_get_line');
        $stream_get_line->expects(static::any())->willReturn(false);
        $this->getMockSocket(['read'])->read();
    }

    /**
     * @return Socket|MockObject
     */
    protected function getMockSocket(array $mockFns): Socket
    {
        $availableFns = ['connect', 'disconnect', 'read', 'write', 'getUniqueIdentifier'];

        return $this->getMockBuilder(Socket::class)
            ->disableOriginalConstructor()
            ->setMethods(array_diff($availableFns, $mockFns))
            ->getMock();
    }
}
