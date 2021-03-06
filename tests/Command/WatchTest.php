<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\Watch;

class WatchTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Command\CommandInterface', new Watch('test-tube'));
    }

    public function testGetCommand()
    {
        $tube = 'test-tube';
        $this->assertEquals("watch $tube", (new Watch($tube))->getCommand());
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testTubeIsValidated()
    {
        new Watch('');
    }

    public function testSuccessfulCommand()
    {
        $tube = 'test-tube';
        $watchingCount = 12;
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("WATCHING $watchingCount");

        $this->assertEquals($watchingCount, (new Watch($tube))->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Watch('test-tube'))->process($this->socket);
    }
}
