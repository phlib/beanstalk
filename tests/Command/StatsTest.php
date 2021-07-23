<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

class StatsTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Stats());
    }

    public function testGetCommand(): void
    {
        static::assertSame('stats', (new Stats())->getCommand());
    }
}
