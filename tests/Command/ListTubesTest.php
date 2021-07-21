<?php

namespace Phlib\Beanstalk\Command;

class ListTubesTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Command\CommandInterface', new ListTubes());
    }

    public function testGetCommand()
    {
        $this->assertEquals("list-tubes", (new ListTubes())->getCommand());
    }
}
