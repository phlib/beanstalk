<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class PutTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Put('data', 1, 0, 60));
    }

    public function testGetCommand(): void
    {
        $data = 'data';
        $bytes = strlen($data);
        static::assertEquals("put 123 456 789 {$bytes}", (new Put($data, 123, 456, 789))->getCommand());
    }

    public function testWithInvalidPriority(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Priority must be integer between 0 and 4,294,967,295
        new Put('data', 4294967296, 123, 456);
    }

    public function testSuccessfulCommand(): void
    {
        $id = 123;
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("INSERTED {$id}");

        static::assertEquals($id, (new Put('data', 123, 456, 789))->process($this->socket));
    }

    public function testErrorThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('DRAINING');
        (new Put('data', 123, 456, 789))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Put('data', 123, 456, 789))->process($this->socket);
    }
}
