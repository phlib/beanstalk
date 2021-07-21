<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;

class ListTubeUsedTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new ListTubeUsed());
    }

    public function testGetCommand(): void
    {
        static::assertEquals('list-tube-used', (new ListTubeUsed())->getCommand());
    }

    public function testSuccessfulCommand(): void
    {
        $tube = 'test-tube';
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("USING {$tube}");
        static::assertEquals($tube, (new ListTubeUsed())->process($this->socket));
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new ListTubeUsed())->process($this->socket);
    }
}
