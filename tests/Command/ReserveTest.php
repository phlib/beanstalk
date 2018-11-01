<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\Reserve;
use Phlib\Beanstalk\Exception\CommandException;

class ReserveTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new Reserve(123));
    }

    /**
     * @dataProvider getCommandDataProvider
     */
    public function testGetCommand($timeout, $command): void
    {
        $this->assertEquals($command, (new Reserve($timeout))->getCommand());
    }

    public function getCommandDataProvider(): array
    {
        return [
            'withTimeout' => [123, 'reserve-with-timeout 123'],
            'justReserve' => [null, 'reserve'],
        ];
    }

    public function testSuccessfulCommand(): void
    {
        $id       = 123;
        $data     = 'Foo Bar';
        $bytes    = strlen($data);
        $response = ['id' => $id, 'body' => $data];

        $this->socket->expects($this->any())
            ->method('read')
            ->will($this->onConsecutiveCalls("RESERVED $id $bytes\r\n", $data . "\r\n"));

        $this->assertEquals($response, (new Reserve())->process($this->socket));
    }

    /**
     * @param string $status
     * @dataProvider failureStatusReturnsFalseDataProvider
     */
    public function testFailureStatusReturnsFalse($status): void
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn($status);
        $this->assertFalse((new Reserve(123))->process($this->socket));
    }

    public function failureStatusReturnsFalseDataProvider(): array
    {
        return [
            'timeout' => ['TIMED_OUT'],
            'deadline' => ['DEADLINE_SOON'],
        ];
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Reserve(123))->process($this->socket);
    }
}
