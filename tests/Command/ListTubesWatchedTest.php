<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

class ListTubesWatchedTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new ListTubesWatched());
    }

    public function testCommandSyntax(): void
    {
        $this->socket->expects(static::once())
            ->method('write')
            ->with('list-tubes-watched');

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('OK 123');

        (new ListTubesWatched())->process($this->socket);
    }
}
