<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\ListTubesWatched;

class ListTubesWatchedTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new ListTubesWatched());
    }

    public function testGetCommand(): void
    {
        $this->assertEquals("list-tubes-watched", (new ListTubesWatched())->getCommand());
    }
}
