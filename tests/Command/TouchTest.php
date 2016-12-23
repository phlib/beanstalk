<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\Touch;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

class TouchTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new Touch(123));
    }

    public function testGetCommand()
    {
        $id = 234;
        $this->assertEquals("touch $id", (new Touch($id))->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $id = 123;
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("TOUCHED");

        $touch = new Touch($id);
        $this->assertInstanceOf(Touch::class, $touch->process($this->socket));
    }

    public function testErrorThrowsException()
    {
        $this->expectException(NotFoundException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('NOT_TOUCHED');
        (new Touch(123))->process($this->socket);
    }

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Touch(123))->process($this->socket);
    }
}
