<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;

class ListTubeUsedTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new ListTubeUsed());
    }

    public function testGetCommand()
    {
        static::assertEquals("list-tube-used", (new ListTubeUsed())->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $tube = 'test-tube';
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("USING $tube");
        static::assertEquals($tube, (new ListTubeUsed())->process($this->socket));
    }

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new ListTubeUsed())->process($this->socket);
    }
}
