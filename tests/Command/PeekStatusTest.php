<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;

class PeekStatusTest extends CommandTestCase
{
    public function testImplementsCommand()
    {
        static::assertInstanceOf(CommandInterface::class, new PeekStatus('ready'));
    }

    /**
     * @param mixed $subject
     * @param string $command
     * @dataProvider getCommandDataProvider
     */
    public function testGetCommand($subject, $command)
    {
        static::assertEquals($command, (new PeekStatus($subject))->getCommand());
    }

    public function getCommandDataProvider()
    {
        return [
            ['ready', 'peek-ready'],
            ['delayed', 'peek-delayed'],
            ['buried', 'peek-buried'],
        ];
    }

    public function testWithInvalidSubject()
    {
        $this->expectException(InvalidArgumentException::class);

        new PeekStatus('foo-bar');
    }

    public function testSuccessfulCommand()
    {
        $id = 123;
        $body = 'Foo Bar';
        $response = [
            'id' => $id,
            'body' => $body,
        ];

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturnOnConsecutiveCalls("FOUND {$id} 54\r\n", $body . "\r\n");

        static::assertEquals($response, (new PeekStatus(PeekStatus::BURIED))->process($this->socket));
    }

    public function testNotFoundThrowsException()
    {
        $this->expectException(NotFoundException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new PeekStatus(PeekStatus::BURIED))->process($this->socket);
    }

    public function testUnknownStatusThrowsException()
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new PeekStatus(PeekStatus::BURIED))->process($this->socket);
    }
}
