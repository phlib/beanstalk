<?php

namespace Phlib\Beanstalk\Command;

class UseTubeTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new UseTube('test-tube'));
    }

    public function testGetCommand()
    {
        $tube = 'test-tube';
        static::assertEquals("use $tube", (new UseTube($tube))->getCommand());
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testTubeIsValidated()
    {
        new UseTube('');
    }

    public function testSuccessfulCommand()
    {
        $tube = 'test-tube';
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("USING $tube");

        static::assertEquals($tube, (new UseTube($tube))->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new UseTube('test-tube'))->process($this->socket);
    }
}
