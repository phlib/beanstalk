<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\StatsTube;

class StatsTubeTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Command\CommandInterface', new StatsTube('test-tube'));
    }

    public function testGetCommand()
    {
        $tube = 'test-tube';
        $this->assertEquals("stats-tube $tube", (new StatsTube($tube))->getCommand());
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testTubeIsValidated()
    {
        new StatsTube('');
    }
}
