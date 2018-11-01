<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\StatsJob;

class StatsJobTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new StatsJob(123));
    }

    public function testGetCommand(): void
    {
        $id = 123;
        $this->assertEquals("stats-job $id", (new StatsJob($id))->getCommand());
    }
}
