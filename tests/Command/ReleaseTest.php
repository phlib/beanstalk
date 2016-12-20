<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\BuriedException;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;

class ReleaseTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Release(123, 456, 789));
    }

    public function testWithInvalidPriority(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Priority must be integer between 0 and 4,294,967,295
        new Release(123, 4294967296, 456);
    }

    public function testWithInvalidDelay(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Delay must be integer between 0 and 4,294,967,295
        new Release(123, 456, 4294967296);
    }

    public function testSuccessfulCommand(): void
    {
        $id = rand();
        $priority = rand(1, ConnectionInterface::MAX_PRIORITY);
        $delay = rand(0, ConnectionInterface::MAX_DELAY);

        $this->socket->expects(static::once())
            ->method('write')
            ->with("release {$id} {$priority} {$delay}");

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('RELEASED');

        $release = new Release($id, $priority, $delay);
        static::assertInstanceOf(Release::class, $release->process($this->socket));
    }

    public function testNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Release(123, 456, 789))->process($this->socket);
    }

    public function testBuriedThrowsException(): void
    {
        $this->expectException(BuriedException::class);

        $jobId = rand();

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('BURIED');

        try {
            (new Release($jobId, 456, 789))->process($this->socket);
        } catch (BuriedException $e) {
            self::assertSame($jobId, $e->getJobId());
            throw $e;
        }
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Release(123, 456, 789))->process($this->socket);
    }
}
