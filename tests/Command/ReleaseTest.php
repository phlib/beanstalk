<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;

class ReleaseTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Release(123, 456, 789));
    }

    public function testGetCommand(): void
    {
        static::assertEquals('release 123 456 789', (new Release(123, 456, 789))->getCommand());
    }

    public function testWithInvalidPriority(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Priority must be integer between 0 and 4,294,967,295
        new Release(123, 4294967296, 456);
    }

    public function testSuccessfulCommand(): void
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('RELEASED');

        $release = new Release(123, 456, 789);
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

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Release(123, 456, 789))->process($this->socket);
    }
}
