<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;

class ReleaseTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new Release(123, 456, 789));
    }

    public function testGetCommand()
    {
        static::assertEquals('release 123 456 789', (new Release(123, 456, 789))->getCommand());
    }

    public function testWithInvalidPriority()
    {
        $this->expectException(InvalidArgumentException::class);

        new Release(123, 'foo', 456);
    }

    public function testSuccessfulCommand()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('RELEASED');

        $release = new Release(123, 456, 789);
        static::assertInstanceOf(Release::class, $release->process($this->socket));
    }

    public function testNotFoundThrowsException()
    {
        $this->expectException(NotFoundException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Release(123, 456, 789))->process($this->socket);
    }

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Release(123, 456, 789))->process($this->socket);
    }
}
