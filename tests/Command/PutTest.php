<?php

namespace Phlib\Beanstalk\Command;

class PutTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new Put('data', 1, 0, 60));
    }

    public function testGetCommand()
    {
        $data  = 'data';
        $bytes = strlen($data);
        static::assertEquals("put 123 456 789 $bytes", (new Put($data, 123, 456, 789))->getCommand());
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testWithInvalidPriority()
    {
        new Put('data', 'foo', 123, 456);
    }

    public function testSuccessfulCommand()
    {
        $id = 123;
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("INSERTED $id");

        static::assertEquals($id, (new Put('data', 123, 456, 789))->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testErrorThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('DRAINING');
        (new Put('data', 123, 456, 789))->process($this->socket);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Put('data', 123, 456, 789))->process($this->socket);
    }
}
