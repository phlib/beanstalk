<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

class ReserveTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Reserve(123));
    }

    /**
     * @dataProvider commandDataProvider
     */
    public function testSuccessfulCommand(?int $timeout, string $command): void
    {
        $id = 123;
        $data = 'Foo Bar';
        $bytes = strlen($data);
        $response = [
            'id' => $id,
            'body' => $data,
        ];

        $this->socket->expects(static::once())
            ->method('write')
            ->with($command);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturnOnConsecutiveCalls("RESERVED {$id} {$bytes}\r\n", $data . "\r\n");

        static::assertSame($response, (new Reserve($timeout))->process($this->socket));
    }

    public function commandDataProvider(): array
    {
        return [
            'withTimeout' => [123, 'reserve-with-timeout 123'],
            'justReserve' => [null, 'reserve'],
        ];
    }

    /**
     * @dataProvider dataFailureStatus
     */
    public function testFailureStatus(string $status): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::RESERVE_NO_JOBS_AVAILABLE_MSG);
        $this->expectExceptionCode(NotFoundException::RESERVE_NO_JOBS_AVAILABLE_CODE);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn($status);
        (new Reserve(123))->process($this->socket);
    }

    public function dataFailureStatus(): array
    {
        return [
            'timeout' => ['TIMED_OUT'],
            'deadline' => ['DEADLINE_SOON'],
        ];
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Reserve(123))->process($this->socket);
    }
}
