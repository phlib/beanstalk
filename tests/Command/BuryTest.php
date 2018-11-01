<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\Bury;
use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

class BuryTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new Bury(123, 123));
    }

    public function testGetCommand(): void
    {
        $id = 123;
        $priority = 1;
        $this->assertEquals("bury $id $priority", (new Bury($id, $priority))->getCommand());
    }

    public function testSuccessfulCommand(): void
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('BURIED');
        $this->assertInstanceOf(Bury::class, (new Bury(123, 123))->process($this->socket));
    }

    public function testNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Bury(123, 123))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Bury(123, 123))->process($this->socket);
    }
}
