<?php

namespace Phlib\Beanstalk\Command;

class TouchTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new Touch(123));
    }

    public function testGetCommand()
    {
        $id = 234;
        static::assertEquals("touch $id", (new Touch($id))->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $id = 123;
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("TOUCHED");

        $touch = new Touch($id);
        static::assertInstanceOf(Touch::class, $touch->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testErrorThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_TOUCHED');
        (new Touch(123))->process($this->socket);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Touch(123))->process($this->socket);
    }
}
