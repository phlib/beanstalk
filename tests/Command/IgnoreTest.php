<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;

class IgnoreTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Ignore('test-tube'));
    }

    public function testSuccessfulCommand(): void
    {
        $tube = sha1(uniqid());

        $this->socket->expects(static::once())
            ->method('write')
            ->with("ignore {$tube}");

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('WATCHING');
        static::assertIsInt((new Ignore($tube))->process($this->socket));
    }

    public function testNotFoundThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_IGNORED');
        (new Ignore('test-tube'))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Ignore('test-tube'))->process($this->socket);
    }
}
