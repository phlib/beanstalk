<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class WatchTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Watch('test-tube'));
    }

    public function testTubeIsValidated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Watch('');
    }

    public function testSuccessfulCommand(): void
    {
        $tube = sha1(uniqid());

        $this->socket->expects(static::once())
            ->method('write')
            ->with("watch {$tube}");

        $watchingCount = 12;
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("WATCHING {$watchingCount}");

        static::assertSame($watchingCount, (new Watch($tube))->process($this->socket));
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Watch('test-tube'))->process($this->socket);
    }
}
