<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

class StatsJobTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new StatsJob(123));
    }

    public function testGetCommand(): void
    {
        $id = 123;
        static::assertSame("stats-job {$id}", (new StatsJob($id))->getCommand());
    }
}
