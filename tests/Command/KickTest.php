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

    public function testSuccessfulCommand(): void
    {
        $bound = rand(1, 100);

        $this->socket->expects(static::once())
            ->method('write')
            ->with("kick {$bound}");

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('KICKED');
        static::assertIsInt((new Kick($bound))->process($this->socket));
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
