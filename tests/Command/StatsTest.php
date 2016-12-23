<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\Stats;

class StatsTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new Stats());
    }

    public function testGetCommand()
    {
        $this->assertEquals('stats', (new Stats())->getCommand());
    }
}
