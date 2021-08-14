<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;

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
            [123, 'reserve-with-timeout 123'],
            [null, 'reserve'],
        ];
    }

    /**
     * @dataProvider failureStatusReturnsFalseDataProvider
     */
    public function testFailureStatusReturnsFalse(string $status): void
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn($status);
        static::assertNull((new Reserve(123))->process($this->socket));
    }

    public function failureStatusReturnsFalseDataProvider(): array
    {
        return [
            ['TIMED_OUT'],
            ['DEADLINE_SOON'],
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
