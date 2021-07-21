<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class UseTubeTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new UseTube('test-tube'));
    }

    public function testGetCommand(): void
    {
        $tube = 'test-tube';
        static::assertEquals("use {$tube}", (new UseTube($tube))->getCommand());
    }

    public function testTubeIsValidated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UseTube('');
    }

    public function testSuccessfulCommand(): void
    {
        $tube = 'test-tube';
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("USING {$tube}");

        static::assertEquals($tube, (new UseTube($tube))->process($this->socket));
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new UseTube('test-tube'))->process($this->socket);
    }
}
