<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Connection;

use Phlib\Beanstalk\Exception\SocketException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class SocketTest extends TestCase
{
    use PHPMock;

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

        (new Socket('host'))->read();
    }

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

        // Add functional read after valid connection
        $stream_get_line = $this->getFunctionMock(__NAMESPACE__, 'stream_get_line');
        $stream_get_line->expects(static::any())->willReturn('');

        (new Socket($host, $port, [
            'timeout' => $timeout,
        ]))->read();
    }

    public function testDisconnectWithValidConnection(): void
    {
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects(static::any())->willReturn(fopen('php://memory', 'r+'));
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects(static::any())->willReturn(true);

        // Add functional read after valid connection
        $stream_get_line = $this->getFunctionMock(__NAMESPACE__, 'stream_get_line');
        $stream_get_line->expects(static::any())->willReturn('');

        // Check the resource is closed
        $fclose = $this->getFunctionMock(__NAMESPACE__, 'fclose');
        $fclose->expects(static::once())->willReturn(true); // <- test here

        $socket = new Socket('host');
        $socket->read();
        $socket->disconnect();
    }

    public function testDisconnectWithNoConnection(): void
    {
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects(static::any())->willReturn(true);
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects(static::any())->willReturn(true);

        // Add functional read to avoid error and allow testing disconnect()
        $stream_get_line = $this->getFunctionMock(__NAMESPACE__, 'stream_get_line');
        $stream_get_line->expects(static::any())->willReturn('');

        // No attempt to close, as open didn't return a valid resource
        $fclose = $this->getFunctionMock(__NAMESPACE__, 'fclose');
        $fclose->expects(static::never());

        $socket = new Socket('host');
        $socket->read();
        $socket->disconnect();
    }

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

        $this->getTestSocket()
            ->write($data);
    }

    public function testWriteThrowsExceptionOnError(): void
    {
        $this->expectException(SocketException::class);

        $fwrite = $this->getFunctionMock(__NAMESPACE__, 'fwrite');
        $fwrite->expects(static::any())
            ->willReturn(0);

        $this->getTestSocket()
            ->write('Some Data');
    }

    public function testReadSuccessfullyFromTheConnection(): void
    {
        $expectedData = 'Some Data';
        $stream_get_line = $this->getFunctionMock(__NAMESPACE__, 'stream_get_line');
        $stream_get_line->expects(static::any())->willReturn($expectedData);
        static::assertSame($expectedData, $this->getTestSocket()->read());
    }

    public function testReadSuccessfullyWithLengthParam(): void
    {
        $expectedData = 'Some Data';

        $feof = $this->getFunctionMock(__NAMESPACE__, 'feof');
        $feof->expects(static::any())->willReturn(false);
        $fread = $this->getFunctionMock(__NAMESPACE__, 'fread');
        $fread->expects(static::any())->willReturn($expectedData);

        static::assertSame($expectedData, $this->getTestSocket()->read(9));
    }

    public function testReadFailsWithBadData(): void
    {
        $this->expectException(SocketException::class);

        $stream_get_line = $this->getFunctionMock(__NAMESPACE__, 'stream_get_line');
        $stream_get_line->expects(static::any())->willReturn(false);
        $this->getTestSocket()->read();
    }

    public function testReadFailsWithBadDataWithLengthParam(): void
    {
        $this->expectException(SocketException::class);

        $feof = $this->getFunctionMock(__NAMESPACE__, 'feof');
        $feof->expects(static::any())->willReturn(false);
        $fread = $this->getFunctionMock(__NAMESPACE__, 'fread');
        $fread->expects(static::any())->willReturn(false);

        $this->getTestSocket()->read(9);
    }

    private function getTestSocket(): Socket
    {
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects(static::any())
            ->willReturn(fopen('php://memory', 'r+'));

        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects(static::any())
            ->willReturn(true);

        return new Socket('host');
    }
}
