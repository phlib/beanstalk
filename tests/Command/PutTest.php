<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class PutTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new Put('data', 1, 0, 60));
    }

    public function testGetCommand()
    {
        $data = 'data';
        $bytes = strlen($data);
        static::assertEquals("put 123 456 789 {$bytes}", (new Put($data, 123, 456, 789))->getCommand());
    }

    public function testWithInvalidPriority()
    {
        $this->expectException(InvalidArgumentException::class);

        new Put('data', 'foo', 123, 456);
    }

    public function testSuccessfulCommand()
    {
        $id = 123;
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("INSERTED {$id}");

        static::assertEquals($id, (new Put('data', 123, 456, 789))->process($this->socket));
    }

    public function testErrorThrowsException()
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('DRAINING');
        (new Put('data', 123, 456, 789))->process($this->socket);
    }

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Put('data', 123, 456, 789))->process($this->socket);
    }
}
