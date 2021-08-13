<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

class StatsJobTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new StatsJob(123));
    }

    public function testCommandSyntax(): void
    {
        $id = rand();

        $this->socket->expects(static::once())
            ->method('write')
            ->with("stats-job {$id}");

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('OK 123');

        (new StatsJob($id))->process($this->socket);
    }
}
