<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;

class KickTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Kick(10));
    }

    public function testGetCommand(): void
    {
        $bound = 10;
        static::assertSame("kick {$bound}", (new Kick($bound))->getCommand());
    }

    public function testSuccessfulCommand(): void
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('KICKED');
        static::assertInternalType('int', (new Kick(10))->process($this->socket));
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Kick(10))->process($this->socket);
    }
}
