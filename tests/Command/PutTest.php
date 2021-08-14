<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class PutTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Put('data', 1, 0, 60));
    }

    public function testWithInvalidPriority(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Priority must be integer between 0 and 4,294,967,295
        new Put('data', 4294967296, 123, 456);
    }

    public function testWithInvalidDelay(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Delay must be integer between 0 and 4,294,967,295
        new Put('data', 123, 4294967296, 456);
    }

    public function testWithInvalidTtr(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // TTR must be integer between 0 and 4,294,967,295
        new Put('data', 123, 456, 4294967296);
    }

    public function testSuccessfulCommand(): void
    {
        $id = rand();
        $priority = rand(1, ConnectionInterface::MAX_PRIORITY);
        $delay = rand(0, ConnectionInterface::MAX_DELAY);
        $ttr = rand(0, ConnectionInterface::MAX_TTR);
        $data = sha1(uniqid());
        $bytes = strlen($data);

        $this->socket->expects(static::exactly(2))
            ->method('write')
            ->withConsecutive(
                ["put {$priority} {$delay} {$ttr} {$bytes}"],
                [$data],
            );

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("INSERTED {$id}");

        static::assertSame($id, (new Put($data, $priority, $delay, $ttr))->process($this->socket));
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
