<?php

namespace Phlib\Beanstalk\Command;

class ReserveTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new Reserve(123));
    }

    /**
     * @dataProvider getCommandDataProvider
     */
    public function testGetCommand($timeout, $command)
    {
        $this->assertEquals($command, (new Reserve($timeout))->getCommand());
    }

    public function getCommandDataProvider()
    {
        return [
            [123, 'reserve-with-timeout 123'],
            [null, 'reserve'],
        ];
    }

    public function testSuccessfulCommand()
    {
        $id       = 123;
        $data     = 'Foo Bar';
        $bytes    = strlen($data);
        $response = ['id' => $id, 'body' => $data];

        $this->socket->expects($this->any())
            ->method('read')
            ->will($this->onConsecutiveCalls("RESERVED $id $bytes\r\n", $data . "\r\n"));

        $this->assertEquals($response, (new Reserve())->process($this->socket));
    }

    /**
     * @param string $status
     * @dataProvider failureStatusReturnsFalseDataProvider
     */
    public function testFailureStatusReturnsFalse($status)
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn($status);
        $this->assertFalse((new Reserve(123))->process($this->socket));
    }

    public function failureStatusReturnsFalseDataProvider()
    {
        return [
            ['TIMED_OUT'],
            ['DEADLINE_SOON'],
        ];
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Reserve(123))->process($this->socket);
    }
}
