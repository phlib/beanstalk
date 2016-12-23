<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\ListTubesWatched;

class ListTubesWatchedTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new ListTubesWatched());
    }

    public function testGetCommand()
    {
        $this->assertEquals("list-tubes-watched", (new ListTubesWatched())->getCommand());
    }
}
