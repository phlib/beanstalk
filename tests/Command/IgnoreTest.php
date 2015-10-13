<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\Ignore;

class IgnoreTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Command\CommandInterface', new Ignore('test-tube'));
    }

    public function testGetCommand()
    {
        $tube = 'test-tube';
        $this->assertEquals("ignore $tube", (new Ignore($tube))->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('WATCHING');
        $this->assertInternalType('int', (new Ignore('test-tube'))->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testNotFoundThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('NOT_IGNORED');
        (new Ignore('test-tube'))->process($this->socket);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Ignore('test-tube'))->process($this->socket);
    }
}
