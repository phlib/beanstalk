<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;

class PeekStatusTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new PeekStatus('ready'));
    }

    public function testWithInvalidSubject(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PeekStatus('foo-bar');
    }

    /**
     * @param mixed $subject
     * @param string $command
     * @dataProvider commandDataProvider
     */
    public function testSuccessfulCommand($subject, $command): void
    {
        $id = 123;
        $body = 'Foo Bar';
        $response = [
            'id' => $id,
            'body' => $body,
        ];

        $this->socket->expects(static::once())
            ->method('write')
            ->with($command);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturnOnConsecutiveCalls("FOUND {$id} 54\r\n", $body . "\r\n");

        static::assertSame($response, (new PeekStatus($subject))->process($this->socket));
    }

    public function commandDataProvider(): array
    {
        return [
            ['ready', 'peek-ready'],
            ['delayed', 'peek-delayed'],
            ['buried', 'peek-buried'],
        ];
    }

    public function testNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::PEEK_STATUS_MSG);
        $this->expectExceptionCode(NotFoundException::PEEK_STATUS_CODE);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new PeekStatus(PeekStatus::BURIED))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new PeekStatus(PeekStatus::BURIED))->process($this->socket);
    }
}
