<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

class PeekTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Peek(10));
    }

    public function testGetCommand(): void
    {
        static::assertEquals('peek 123', (new Peek(123))->getCommand());
    }

    public function testSuccessfulCommand(): void
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

        static::assertEquals($response, (new Peek(10))->process($this->socket));
    }

    public function testNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Peek(10))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Peek(10))->process($this->socket);
    }
}
