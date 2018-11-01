<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\Kick;
use Phlib\Beanstalk\Exception\CommandException;

class KickTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new Kick(10));
    }

    public function testGetCommand(): void
    {
        $bound = 10;
        $this->assertEquals("kick $bound", (new Kick($bound))->getCommand());
    }

    public function testSuccessfulCommand(): void
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('KICKED');
        $this->assertInternalType('int', (new Kick(10))->process($this->socket));
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Kick(10))->process($this->socket);
    }
}
