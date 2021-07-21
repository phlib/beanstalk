<?php

namespace Phlib\Beanstalk\Command;

class ListTubesWatchedTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new ListTubesWatched());
    }

    public function testGetCommand()
    {
        static::assertEquals('list-tubes-watched', (new ListTubesWatched())->getCommand());
    }
}
