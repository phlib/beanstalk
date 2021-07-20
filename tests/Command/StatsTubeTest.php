<?php

namespace Phlib\Beanstalk\Command;

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

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testTubeIsValidated()
    {
        new StatsTube('');
    }
}
