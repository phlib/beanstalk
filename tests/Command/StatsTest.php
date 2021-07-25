<?php

namespace Phlib\Beanstalk\Command;

class StatsTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new Stats());
    }

    public function testGetCommand()
    {
        static::assertEquals('stats', (new Stats())->getCommand());
    }
}
