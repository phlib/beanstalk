<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\StatsJob;

class StatsJobTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Command\CommandInterface', new StatsJob(123));
    }

    public function testGetCommand()
    {
        $id = 123;
        $this->assertEquals("stats-job $id", (new StatsJob($id))->getCommand());
    }
}
