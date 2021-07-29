<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

class TouchTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Touch(123));
    }

    public function testGetCommand(): void
    {
        $id = 234;
        static::assertSame("touch {$id}", (new Touch($id))->getCommand());
    }

    public function testSuccessfulCommand(): void
    {
        $id = 123;
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('TOUCHED');

        $touch = new Touch($id);
        static::assertInstanceOf(Touch::class, $touch->process($this->socket));
    }

    public function testErrorThrowsException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_TOUCHED');
        (new Touch(123))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Touch(123))->process($this->socket);
    }
}
