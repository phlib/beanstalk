<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\InvalidArgumentException;

class StatsTubeTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new StatsTube('test-tube'));
    }

    public function testCommandSyntax(): void
    {
        $tube = sha1(uniqid());

        $this->socket->expects(static::once())
            ->method('write')
            ->with("stats-tube {$tube}");

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('OK 123');

        (new StatsTube($tube))->process($this->socket);
    }

    public function testTubeIsValidated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StatsTube('');
    }
}
