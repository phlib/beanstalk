<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\UseTube;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class UseTubeTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new UseTube('test-tube'));
    }

    public function testGetCommand()
    {
        $tube = 'test-tube';
        $this->assertEquals("use $tube", (new UseTube($tube))->getCommand());
    }

    public function testTubeIsValidated()
    {
        $this->expectException(InvalidArgumentException::class);
        new UseTube('');
    }

    public function testSuccessfulCommand()
    {
        $tube = 'test-tube';
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("USING $tube");

        $this->assertEquals($tube, (new UseTube($tube))->process($this->socket));
    }

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new UseTube('test-tube'))->process($this->socket);
    }
}
