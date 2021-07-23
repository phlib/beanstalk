<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

class ListTubesWatchedTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new ListTubesWatched());
    }

    public function testGetCommand(): void
    {
        static::assertSame('list-tubes-watched', (new ListTubesWatched())->getCommand());
    }
}
