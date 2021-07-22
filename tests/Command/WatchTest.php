<?php

namespace Phlib\Beanstalk\Command;

class WatchTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new Watch('test-tube'));
    }

    public function testGetCommand()
    {
        $tube = 'test-tube';
        static::assertEquals("watch $tube", (new Watch($tube))->getCommand());
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
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("WATCHING $watchingCount");

        static::assertEquals($watchingCount, (new Watch($tube))->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Watch('test-tube'))->process($this->socket);
    }
}
