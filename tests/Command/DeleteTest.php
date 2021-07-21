<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

class DeleteTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Delete(123));
    }

    public function testGetCommand(): void
    {
        $id = 123;
        static::assertEquals("delete {$id}", (new Delete($id))->getCommand());
    }

    public function testSuccessfulCommand(): void
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('DELETED');
        static::assertInstanceOf(Delete::class, (new Delete(123))->process($this->socket));
    }

    public function testNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Delete(123))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Delete(123))->process($this->socket);
    }
}
