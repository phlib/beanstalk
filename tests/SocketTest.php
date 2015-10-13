<?php

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Socket;
use phpmock\phpunit\PHPMock;

class SocketTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    public function testImplementsInterface()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\SocketInterface', new Socket('localhost'));
    }

    public function testGetUniqueIdentifier()
    {
        $socket1 = new Socket('localhost', 11300);
        $socket2 = new Socket('localhost', 11301);
        $this->assertNotEquals($socket1->getUniqueIdentifier(), $socket2->getUniqueIdentifier());
    }

    public function testConnectOnSuccessReturnsSelf()
    {
        $fsockopen = $this->getFunctionMock('\Phlib\Beanstalk', 'fsockopen');
        $fsockopen->expects($this->any())->willReturn(true);
        $stream_set_timeout = $this->getFunctionMock('\Phlib\Beanstalk', 'stream_set_timeout');
        $stream_set_timeout->expects($this->any())->willReturn(true);

        $this->assertInstanceOf('\Phlib\Beanstalk\Socket', (new Socket('host'))->connect());
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\SocketException
     */
    public function testConnectOnFailureThrowsError()
    {
        $fsockopen = $this->getFunctionMock('\Phlib\Beanstalk', 'fsockopen');
        $fsockopen->expects($this->any())->willReturnCallback(function ($host, $port, &$errNum, &$errStr, $timeout) {
            $errNum = 123;
            $errStr = 'Testing All The Things';
            return false;
        });
        $stream_set_timeout = $this->getFunctionMock('\Phlib\Beanstalk', 'stream_set_timeout');
        $stream_set_timeout->expects($this->any())->willReturn(true);

        (new Socket('host'))->connect();
    }

    public function testConnectsWithTheCorrectDetails()
    {
        $host    = 'someHost';
        $port    = 145234;
        $timeout = 432;

        $fsockopen = $this->getFunctionMock('\Phlib\Beanstalk', 'fsockopen');
        $fsockopen->expects($this->any())
            ->with(
                $this->equalTo($host),
                $this->equalTo($port),
                $this->equalTo(null), // errNum
                $this->equalTo(null), // errStr
                $this->equalTo($timeout)
            )
            ->willReturn('(socket)');
        $stream_set_timeout = $this->getFunctionMock('\Phlib\Beanstalk', 'stream_set_timeout');
        $stream_set_timeout->expects($this->any())->willReturn(true);

        (new Socket($host, $port, ['timeout' => $timeout]))->connect();
    }

    public function testWriteSuccessfullyToTheConnection()
    {
        $data = 'Some Data';
        $dataLength = 9 + strlen(Socket::EOL);

        $fwrite = $this->getFunctionMock('\Phlib\Beanstalk', 'fwrite');
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
        $fwrite = $this->getFunctionMock('\Phlib\Beanstalk', 'fwrite');
        $fwrite->expects($this->any())
            ->willReturn(0);

        $this->getMockSocket(['write'])
            ->write('Some Data');
    }

    public function testReadSuccessfullyFromTheConnection()
    {
        $expectedData = 'Some Data';
        $stream_get_line = $this->getFunctionMock('\Phlib\Beanstalk', 'stream_get_line');
        $stream_get_line->expects($this->any())->willReturn($expectedData);
        $this->assertEquals($expectedData, $this->getMockSocket(['read'])->read());
    }

    public function testReadSuccessfullyWithLengthParam()
    {
        $expectedData = 'Some Data';

        $feof = $this->getFunctionMock('\Phlib\Beanstalk', 'feof');
        $feof->expects($this->any())->willReturn(false);
        $fread = $this->getFunctionMock('\Phlib\Beanstalk', 'fread');
        $fread->expects($this->any())->willReturn($expectedData);

        $this->assertEquals($expectedData, $this->getMockSocket(['read'])->read(9));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\SocketException
     */
    public function testReadFailsWithBadData()
    {
        $stream_get_line = $this->getFunctionMock('\Phlib\Beanstalk', 'stream_get_line');
        $stream_get_line->expects($this->any())->willReturn(false);
        $this->getMockSocket(['read'])->read();
    }

    protected function getMockSocket(array $mockFns)
    {
        $availableFns = ['connect', 'disconnect', 'read', 'write', 'getUniqueIdentifier'];

        return $this->getMockBuilder('\Phlib\Beanstalk\Socket')
            ->disableOriginalConstructor()
            ->setMethods(array_diff($availableFns, $mockFns))
            ->getMock();
    }
}
