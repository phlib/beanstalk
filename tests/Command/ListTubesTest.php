<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\ListTubes;

class ListTubesTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new ListTubes());
    }

    public function testGetCommand()
    {
        $this->assertEquals("list-tubes", (new ListTubes())->getCommand());
    }
}
