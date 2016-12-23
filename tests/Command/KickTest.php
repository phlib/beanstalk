<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\Kick;
use Phlib\Beanstalk\Exception\CommandException;

class KickTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new Kick(10));
    }

    public function testGetCommand()
    {
        $bound = 10;
        $this->assertEquals("kick $bound", (new Kick($bound))->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('KICKED');
        $this->assertInternalType('int', (new Kick(10))->process($this->socket));
    }

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Kick(10))->process($this->socket);
    }
}
