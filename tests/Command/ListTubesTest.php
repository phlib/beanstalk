<?php

namespace Phlib\Beanstalk\Command;

class ListTubesTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new ListTubes());
    }

    public function testGetCommand()
    {
        static::assertEquals("list-tubes", (new ListTubes())->getCommand());
    }
}
