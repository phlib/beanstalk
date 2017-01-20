<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\Put;
use Phlib\Beanstalk\Exception\BuriedException;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\DrainingException;

class PutTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new Put('data', 1, 0, 60));
    }

    public function testGetCommand()
    {
        $data  = 'data';
        $bytes = strlen($data);
        $this->assertEquals("put 123 456 789 $bytes", (new Put($data, 123, 456, 789))->getCommand());
    }

    public function testWithInvalidPriority()
    {
        $this->expectException(\TypeError::class);
        new Put('data', 'foo', 123, 456);
    }

    public function testSuccessfulCommand()
    {
        $id = 123;
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("INSERTED $id");

        $this->assertEquals($id, (new Put('data', 123, 456, 789))->process($this->socket));
    }

    public function testErrorThrowsException()
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('JOB_TOO_BIG');
        (new Put('data', 123, 456, 789))->process($this->socket);
    }

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Put('data', 123, 456, 789))->process($this->socket);
    }

    public function testDrainingStatusThrowsException()
    {
        $this->expectException(DrainingException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('DRAINING');
        (new Put('data', 123, 456, 789))->process($this->socket);
    }

    public function testBuriedStatusThrowsException()
    {
        $this->expectException(BuriedException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('BURIED');
        (new Put('data', 123, 456, 789))->process($this->socket);
    }
}
