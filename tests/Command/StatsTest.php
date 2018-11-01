<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\Stats;

class StatsTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new Stats());
    }

    public function testGetCommand(): void
    {
        $this->assertEquals('stats', (new Stats())->getCommand());
    }
}
