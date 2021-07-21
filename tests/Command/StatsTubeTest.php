<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\InvalidArgumentException;

class StatsTubeTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new StatsTube('test-tube'));
    }

    public function testGetCommand()
    {
        $tube = 'test-tube';
        static::assertEquals("stats-tube {$tube}", (new StatsTube($tube))->getCommand());
    }

    public function testTubeIsValidated()
    {
        $this->expectException(InvalidArgumentException::class);

        new StatsTube('');
    }
}
