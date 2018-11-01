<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\Peek;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;

class PeekTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new Peek('ready'));
    }

    /**
     * @param mixed $subject
     * @param string $command
     * @dataProvider getCommandDataProvider
     */
    public function testGetCommand($subject, $command): void
    {
        $this->assertEquals($command, (new Peek($subject))->getCommand());
    }

    public function getCommandDataProvider(): array
    {
        return [
            'ready' => ['ready', 'peek-ready'],
            'delayed' => ['delayed', 'peek-delayed'],
            'buried' => ['buried', 'peek-buried'],
            'named' => ['123', 'peek 123'],
        ];
    }

    public function testWithInvalidSubject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Peek('foo-bar');
    }

    public function testSuccessfulCommand(): void
    {
        $id       = 123;
        $body     = 'Foo Bar';
        $response = ['id' => $id, 'body' => $body];

        $this->socket->expects($this->any())
            ->method('read')
            ->will($this->onConsecutiveCalls("FOUND $id 123\r\n", $body . "\r\n"));

        $this->assertEquals($response, (new Peek(10))->process($this->socket));
    }

    public function testNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Peek(10))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Peek(10))->process($this->socket);
    }
}
