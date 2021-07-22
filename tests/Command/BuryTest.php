<?php

namespace Phlib\Beanstalk\Command;

class BuryTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new Bury(123, 123));
    }

    public function testGetCommand()
    {
        $id = 123;
        $priority = 1;
        static::assertEquals("bury $id $priority", (new Bury($id, $priority))->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('BURIED');
        static::assertInstanceOf(Bury::class, (new Bury(123, 123))->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testNotFoundThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Bury(123, 123))->process($this->socket);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Bury(123, 123))->process($this->socket);
    }
}
