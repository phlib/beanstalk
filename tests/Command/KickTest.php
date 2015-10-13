<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\Kick;

class KickTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Command\CommandInterface', new Kick(10));
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

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Kick(10))->process($this->socket);
    }
}
