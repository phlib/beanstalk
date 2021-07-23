<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\InvalidArgumentException;

class StatsTubeTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new StatsTube('test-tube'));
    }

    public function testGetCommand(): void
    {
        $tube = 'test-tube';
        static::assertSame("stats-tube {$tube}", (new StatsTube($tube))->getCommand());
    }

    public function testTubeIsValidated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StatsTube('');
    }
}
