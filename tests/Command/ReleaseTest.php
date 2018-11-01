<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\Release;
use Phlib\Beanstalk\Exception\BuriedException;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

class ReleaseTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new Release(123, 456, 789));
    }

    public function testGetCommand(): void
    {
        $this->assertEquals('release 123 456 789', (new Release(123, 456, 789))->getCommand());
    }

    public function testSuccessfulCommand(): void
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("RELEASED");

        $release = new Release(123, 456, 789);
        $this->assertInstanceOf(Release::class, $release->process($this->socket));
    }

    public function testNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Release(123, 456, 789))->process($this->socket);
    }

    public function testBuriedThrowsException(): void
    {
        $this->expectException(BuriedException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('BURIED');
        (new Release(123, 456, 789))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Release(123, 456, 789))->process($this->socket);
    }
}
