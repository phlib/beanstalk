<?php

namespace Phlib\Beanstalk\Command;

class PeekTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        $this->assertInstanceOf(CommandInterface::class, new Peek('ready'));
    }

    /**
     * @param mixed $subject
     * @param string $command
     * @dataProvider getCommandDataProvider
     */
    public function testGetCommand($subject, $command)
    {
        $this->assertEquals($command, (new Peek($subject))->getCommand());
    }

    public function getCommandDataProvider()
    {
        return [
            ['ready', 'peek-ready'],
            ['delayed', 'peek-delayed'],
            ['buried', 'peek-buried'],
            ['123', 'peek 123'],
        ];
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testWithInvalidSubject()
    {
        new Peek('foo-bar');
    }

    public function testSuccessfulCommand()
    {
        $id       = 123;
        $body     = 'Foo Bar';
        $response = ['id' => $id, 'body' => $body];

        $this->socket->expects($this->any())
            ->method('read')
            ->will($this->onConsecutiveCalls("FOUND $id 123\r\n", $body . "\r\n"));

        $this->assertEquals($response, (new Peek(10))->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testNotFoundThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Peek(10))->process($this->socket);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testUnknownStatusThrowsException()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Peek(10))->process($this->socket);
    }
}
