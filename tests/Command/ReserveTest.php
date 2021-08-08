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
     * @dataProvider getCommandDataProvider
     */
    public function testGetCommand(?int $timeout, string $command): void
    {
        static::assertSame($command, (new Reserve($timeout))->getCommand());
    }

    public function getCommandDataProvider(): array
    {
        return [
            [123, 'reserve-with-timeout 123'],
            [null, 'reserve'],
        ];
    }

    public function testSuccessfulCommand(): void
    {
        $id = 123;
        $data = 'Foo Bar';
        $bytes = strlen($data);
        $response = [
            'id' => $id,
            'body' => $data,
        ];

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturnOnConsecutiveCalls("RESERVED {$id} {$bytes}\r\n", $data . "\r\n");

        static::assertSame($response, (new Reserve())->process($this->socket));
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
