<?php

namespace Phlib\Beanstalk\Command;

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

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Kick(10))->process($this->socket);
    }
}
