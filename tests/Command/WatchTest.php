<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\Watch;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class WatchTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new Watch('test-tube'));
    }

    public function testGetCommand()
    {
        $tube = 'test-tube';
        $this->assertEquals("watch $tube", (new Watch($tube))->getCommand());
    }

    public function testTubeIsValidated()
    {
        $this->expectException(InvalidArgumentException::class);
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

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Watch('test-tube'))->process($this->socket);
    }
}
