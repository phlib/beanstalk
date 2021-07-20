<?php

namespace Phlib\Beanstalk\Connection;

use phpmock\phpunit\PHPMock;

class SocketTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    public function testImplementsInterface()
    {
        $this->assertInstanceOf(SocketInterface::class, new Socket('localhost'));
    }

    public function testGetUniqueIdentifier()
    {
        $socket1 = new Socket('localhost', 11300);
        $socket2 = new Socket('localhost', 11301);
        $this->assertNotEquals($socket1->getUniqueIdentifier(), $socket2->getUniqueIdentifier());
    }

    public function testConnectOnSuccessReturnsSelf()
    {
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects($this->any())->willReturn(true);
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects($this->any())->willReturn(true);

        $this->assertInstanceOf(Socket::class, (new Socket('host'))->connect());
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\SocketException
     */
    public function testConnectOnFailureThrowsError()
    {
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects($this->any())->willReturnCallback(function ($host, $port, &$errNum, &$errStr, $timeout) {
            $errNum = 123;
            $errStr = 'Testing All The Things';
            return false;
        });
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects($this->any())->willReturn(true);

        (new Socket('host'))->connect();
    }

    public function testConnectsWithTheCorrectDetails()
    {
        $host    = 'someHost';
        $port    = 145234;
        $timeout = 432;

        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects($this->any())
            ->with(
                $this->equalTo($host),
                $this->equalTo($port),
                $this->equalTo(null), // errNum
                $this->equalTo(null), // errStr
                $this->equalTo($timeout)
            )
            ->willReturn('(socket)');
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects($this->any())->willReturn(true);

        (new Socket($host, $port, ['timeout' => $timeout]))->connect();
    }

    public function testDisconnectWithValidConnection()
    {
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects($this->any())->willReturn(fopen('php://memory', 'r+'));
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects($this->any())->willReturn(true);
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fclose');
        $fsockopen->expects($this->once())->willReturn(true); // <- test here

        $socket = new Socket('host');
        $socket->connect();
        $socket->disconnect();
    }

    public function testDisconnectWithNoConnection()
    {
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fsockopen');
        $fsockopen->expects($this->any())->willReturn(true);
        $stream_set_timeout = $this->getFunctionMock(__NAMESPACE__, 'stream_set_timeout');
        $stream_set_timeout->expects($this->any())->willReturn(true);
        $fsockopen = $this->getFunctionMock(__NAMESPACE__, 'fclose');
        $fsockopen->expects($this->never()); // <- test here

        $socket = new Socket('host');
        $socket->connect();
        $socket->disconnect();
    }

    public function testWriteSuccessfullyToTheConnection()
    {
        $data = 'Some Data';
        $dataLength = 9 + strlen(Socket::EOL);

        $fwrite = $this->getFunctionMock(__NAMESPACE__, 'fwrite');
        $fwrite->expects($this->any())
            ->with(
                $this->anything(),
                $this->stringEndsWith(Socket::EOL),
                $this->equalTo($dataLength)
            )
            ->willReturn($dataLength);

        $this->getMockSocket(['write'])
            ->write($data);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\SocketException
     */
    public function testWriteThrowsExceptionOnError()
    {
        $fwrite = $this->getFunctionMock(__NAMESPACE__, 'fwrite');
        $fwrite->expects($this->any())
            ->willReturn(0);

        $this->getMockSocket(['write'])
            ->write('Some Data');
    }

    public function testReadSuccessfullyFromTheConnection()
    {
        $expectedData = 'Some Data';
        $stream_get_line = $this->getFunctionMock(__NAMESPACE__, 'stream_get_line');
        $stream_get_line->expects($this->any())->willReturn($expectedData);
        $this->assertEquals($expectedData, $this->getMockSocket(['read'])->read());
    }

    public function testReadSuccessfullyWithLengthParam()
    {
        $expectedData = 'Some Data';

        $feof = $this->getFunctionMock(__NAMESPACE__, 'feof');
        $feof->expects($this->any())->willReturn(false);
        $fread = $this->getFunctionMock(__NAMESPACE__, 'fread');
        $fread->expects($this->any())->willReturn($expectedData);

        $this->assertEquals($expectedData, $this->getMockSocket(['read'])->read(9));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\SocketException
     */
    public function testReadFailsWithBadData()
    {
        $stream_get_line = $this->getFunctionMock(__NAMESPACE__, 'stream_get_line');
        $stream_get_line->expects($this->any())->willReturn(false);
        $this->getMockSocket(['read'])->read();
    }

    /**
     * @param array $mockFns
     * @return Socket|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockSocket(array $mockFns)
    {
        $availableFns = ['connect', 'disconnect', 'read', 'write', 'getUniqueIdentifier'];

        return $this->getMockBuilder(Socket::class)
            ->disableOriginalConstructor()
            ->setMethods(array_diff($availableFns, $mockFns))
            ->getMock();
    }
}
