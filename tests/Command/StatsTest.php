<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

class StatsTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Stats());
    }

    public function testCommandSyntax(): void
    {
        $this->socket->expects(static::once())
            ->method('write')
            ->with('stats');

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('OK 123');

        (new Stats())->process($this->socket);
    }
}
