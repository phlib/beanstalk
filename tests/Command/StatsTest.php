<?php

namespace Phlib\Beanstalk\Command;

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
