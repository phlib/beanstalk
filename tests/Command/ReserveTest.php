<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;

class ReserveTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new Reserve(123));
    }

    /**
     * @dataProvider getCommandDataProvider
     */
    public function testGetCommand($timeout, $command)
    {
        static::assertEquals($command, (new Reserve($timeout))->getCommand());
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

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturnOnConsecutiveCalls("RESERVED $id $bytes\r\n", $data . "\r\n");

        static::assertEquals($response, (new Reserve())->process($this->socket));
    }

    /**
     * @param string $status
     * @dataProvider failureStatusReturnsFalseDataProvider
     */
    public function testFailureStatusReturnsFalse($status)
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn($status);
        static::assertFalse((new Reserve(123))->process($this->socket));
    }

    public function failureStatusReturnsFalseDataProvider()
    {
        return [
            ['TIMED_OUT'],
            ['DEADLINE_SOON'],
        ];
    }

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Reserve(123))->process($this->socket);
    }
}
