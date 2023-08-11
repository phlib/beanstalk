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

    public function testSuccessfulCommand(): void
    {
        $id = rand();
        $body = 'Foo Bar';
        $response = [
            'id' => $id,
            'body' => $body,
        ];

        $this->socket->expects(static::once())
            ->method('write')
            ->with("peek {$id}");

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturnOnConsecutiveCalls("FOUND {$id} 54\r\n", $body . "\r\n");

        static::assertSame($response, (new Peek($id))->process($this->socket));
    }

    public function testNotFoundThrowsException(): void
    {
        $jobId = rand();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(sprintf(NotFoundException::JOB_ID_MSG_F, $jobId));
        $this->expectExceptionCode(NotFoundException::JOB_ID_CODE);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Peek($jobId))->process($this->socket);
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
