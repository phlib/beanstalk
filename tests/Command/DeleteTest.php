<?php

namespace Phlib\Beanstalk\Command;

class DeleteTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new Delete(123));
    }

    public function testGetCommand()
    {
        $id = 123;
        $this->assertEquals("delete $id", (new Delete($id))->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('DELETED');
        $this->assertInstanceOf(Delete::class, (new Delete(123))->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testNotFoundThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Delete(123))->process($this->socket);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Delete(123))->process($this->socket);
    }
}
