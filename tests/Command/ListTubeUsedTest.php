<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\ListTubeUsed;
use Phlib\Beanstalk\Exception\CommandException;

class ListTubeUsedTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new ListTubeUsed());
    }

    public function testGetCommand()
    {
        $this->assertEquals("list-tube-used", (new ListTubeUsed())->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $tube = 'test-tube';
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("USING $tube");
        $this->assertEquals($tube, (new ListTubeUsed())->process($this->socket));
    }

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new ListTubeUsed())->process($this->socket);
    }
}
