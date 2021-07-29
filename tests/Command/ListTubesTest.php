<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

class ListTubesTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new ListTubes());
    }

    public function testGetCommand(): void
    {
        static::assertSame('list-tubes', (new ListTubes())->getCommand());
    }
}
