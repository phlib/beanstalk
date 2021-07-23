<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

class BuryTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Bury(123, 123));
    }

    public function testGetCommand(): void
    {
        $id = 123;
        $priority = 1;
        static::assertSame("bury {$id} {$priority}", (new Bury($id, $priority))->getCommand());
    }

    public function testSuccessfulCommand(): void
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('BURIED');
        static::assertInstanceOf(Bury::class, (new Bury(123, 123))->process($this->socket));
    }

    public function testNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Bury(123, 123))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Bury(123, 123))->process($this->socket);
    }
}
