<?php

namespace Phlib\Beanstalk\Command;

class StatsJobTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new StatsJob(123));
    }

    public function testGetCommand()
    {
        $id = 123;
        static::assertEquals("stats-job {$id}", (new StatsJob($id))->getCommand());
    }
}
