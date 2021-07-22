<?php

namespace Phlib\Beanstalk\Command;

class IgnoreTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new Ignore('test-tube'));
    }

    public function testGetCommand()
    {
        $tube = 'test-tube';
        static::assertEquals("ignore $tube", (new Ignore($tube))->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('WATCHING');
        static::assertInternalType('int', (new Ignore('test-tube'))->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testNotFoundThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_IGNORED');
        (new Ignore('test-tube'))->process($this->socket);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Ignore('test-tube'))->process($this->socket);
    }
}
