<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\ListTubeUsed;
use Phlib\Beanstalk\Exception\CommandException;

class ListTubeUsedTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new ListTubeUsed());
    }

    public function testGetCommand(): void
    {
        $this->assertEquals("list-tube-used", (new ListTubeUsed())->getCommand());
    }

    public function testSuccessfulCommand(): void
    {
        $tube = 'test-tube';
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("USING $tube");
        $this->assertEquals($tube, (new ListTubeUsed())->process($this->socket));
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new ListTubeUsed())->process($this->socket);
    }
}
