<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\ListTubes;

class ListTubesTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new ListTubes());
    }

    public function testGetCommand(): void
    {
        $this->assertEquals("list-tubes", (new ListTubes())->getCommand());
    }
}
