<?php

namespace Phlib\Beanstalk\Command;

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
