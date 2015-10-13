<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\ListTubesWatched;

class ListTubesWatchedTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Command\CommandInterface', new ListTubesWatched());
    }

    public function testGetCommand()
    {
        $this->assertEquals("list-tubes-watched", (new ListTubesWatched())->getCommand());
    }
}
