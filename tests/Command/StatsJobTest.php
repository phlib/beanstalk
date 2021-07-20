<?php

namespace Phlib\Beanstalk\Command;

class StatsJobTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new StatsJob(123));
    }

    public function testGetCommand()
    {
        $id = 123;
        $this->assertEquals("stats-job $id", (new StatsJob($id))->getCommand());
    }
}
