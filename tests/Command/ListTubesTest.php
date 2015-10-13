<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\ListTubes;

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
