<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;

class ListTubeUsedTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new ListTubeUsed());
    }

    public function testSuccessfulCommand(): void
    {
        $tube = sha1(uniqid());

        $this->socket->expects(static::once())
            ->method('write')
            ->with('list-tube-used');

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("USING {$tube}");
        static::assertSame($tube, (new ListTubeUsed())->process($this->socket));
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new ListTubeUsed())->process($this->socket);
    }
}
