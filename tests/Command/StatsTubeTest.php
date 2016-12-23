<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\StatsTube;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class StatsTubeTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new StatsTube('test-tube'));
    }

    public function testGetCommand()
    {
        $tube = 'test-tube';
        $this->assertEquals("stats-tube $tube", (new StatsTube($tube))->getCommand());
    }

    public function testTubeIsValidated()
    {
        $this->expectException(InvalidArgumentException::class);
        new StatsTube('');
    }
}
