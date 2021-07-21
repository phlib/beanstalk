<?php

namespace Phlib\Beanstalk\Command;

class StatsTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Command\CommandInterface', new Stats());
    }

    public function testGetCommand()
    {
        $this->assertEquals('stats', (new Stats())->getCommand());
    }
}
