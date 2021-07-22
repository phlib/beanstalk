<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;

class KickTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new Kick(10));
    }

    public function testGetCommand()
    {
        $bound = 10;
        static::assertEquals("kick $bound", (new Kick($bound))->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('KICKED');
        static::assertInternalType('int', (new Kick(10))->process($this->socket));
    }

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Kick(10))->process($this->socket);
    }
}
