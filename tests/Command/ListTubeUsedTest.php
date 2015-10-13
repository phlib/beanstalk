<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\ListTubeUsed;

class ListTubeUsedTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Command\CommandInterface', new ListTubeUsed());
    }

    public function testGetCommand()
    {
        $this->assertEquals("list-tube-used", (new ListTubeUsed())->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $tube = 'test-tube';
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("USING $tube");
        $this->assertEquals($tube, (new ListTubeUsed())->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new ListTubeUsed())->process($this->socket);
    }
}
