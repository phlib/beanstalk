<?php

namespace Phlib\Beanstalk\Command;

class ListTubeUsedTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new ListTubeUsed());
    }

    public function testGetCommand()
    {
        static::assertEquals("list-tube-used", (new ListTubeUsed())->getCommand());
    }

    public function testSuccessfulCommand()
    {
        $tube = 'test-tube';
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("USING $tube");
        static::assertEquals($tube, (new ListTubeUsed())->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new ListTubeUsed())->process($this->socket);
    }
}
