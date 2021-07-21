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

    public function testGetCommand(): void
    {
        $tube = 'test-tube';
        static::assertEquals("ignore {$tube}", (new Ignore($tube))->getCommand());
    }

    public function testSuccessfulCommand(): void
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('WATCHING');
        static::assertInternalType('int', (new Ignore('test-tube'))->process($this->socket));
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
