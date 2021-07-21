<?php

namespace Phlib\Beanstalk\Command;

class ReleaseTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Command\CommandInterface', new Release(123, 456, 789));
    }

    public function testGetCommand()
    {
        $this->assertEquals('release 123 456 789', (new Release(123, 456, 789))->getCommand());
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testWithInvalidPriority()
    {
        new Release(123, 'foo', 456);
    }

    public function testSuccessfulCommand()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("RELEASED");

        $release = new Release(123, 456, 789);
        $this->assertInstanceOf('\Phlib\Beanstalk\Command\Release', $release->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testNotFoundThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Release(123, 456, 789))->process($this->socket);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Release(123, 456, 789))->process($this->socket);
    }
}
