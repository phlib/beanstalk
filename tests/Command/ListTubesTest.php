<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

class ListTubesTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new ListTubes());
    }

    public function testCommandSyntax(): void
    {
        $this->socket->expects(static::once())
            ->method('write')
            ->with('list-tubes');

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('OK 123');

        (new ListTubes())->process($this->socket);
    }
}
