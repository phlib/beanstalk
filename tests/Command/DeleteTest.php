<?php

namespace Phlib\Beanstalk\Command;

class DeleteTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new Delete(123));
    }

    public function testGetCommand()
    {
        $id = 123;
        static::assertEquals("delete $id", (new Delete($id))->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('DELETED');
        static::assertInstanceOf(Delete::class, (new Delete(123))->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testNotFoundThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Delete(123))->process($this->socket);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Delete(123))->process($this->socket);
    }
}
