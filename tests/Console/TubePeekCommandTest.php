<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;

class TubePeekCommandTest extends ConsoleTestCase
{
    protected function setUpCommand(): AbstractCommand
    {
        return new TubePeekCommand($this->factory);
    }

    public function testTubePeekBuried(): void
    {
        $tube = sha1(uniqid());
        $jobId = rand();
        $body = sha1(uniqid());

        $this->connection->expects(static::once())
            ->method('useTube')
            ->with($tube);

        $this->connection->expects(static::once())
            ->method('peekBuried')
            ->willReturn([
                'id' => $jobId,
                'body' => $body,
            ]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
            'status' => 'buried',
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString("Job ID: {$jobId}", $output);
        static::assertStringContainsString($body, $output);
    }

    public function testTubePeekDelayed(): void
    {
        $tube = sha1(uniqid());
        $jobId = rand();
        $body = sha1(uniqid());

        $this->connection->expects(static::once())
            ->method('useTube')
            ->with($tube);

        $this->connection->expects(static::once())
            ->method('peekDelayed')
            ->willReturn([
                'id' => $jobId,
                'body' => $body,
            ]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
            'status' => 'delayed',
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString("Job ID: {$jobId}", $output);
        static::assertStringContainsString($body, $output);
    }

    public function testTubePeekReady(): void
    {
        $tube = sha1(uniqid());
        $jobId = rand();
        $body = sha1(uniqid());

        $this->connection->expects(static::once())
            ->method('useTube')
            ->with($tube);

        $this->connection->expects(static::once())
            ->method('peekReady')
            ->willReturn([
                'id' => $jobId,
                'body' => $body,
            ]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
            'status' => 'ready',
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString("Job ID: {$jobId}", $output);
        static::assertStringContainsString($body, $output);
    }

    public function testTubePeekInvalidStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $tube = sha1(uniqid());

        $this->connection->expects(static::never())
            ->method(static::anything());

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
            'status' => sha1(uniqid()),
        ]);
    }

    public function testTubePeekNoResult(): void
    {
        $tube = sha1(uniqid());

        $this->connection->expects(static::once())
            ->method('useTube')
            ->with($tube);

        $this->connection->expects(static::once())
            ->method('peekBuried')
            ->willThrowException(new NotFoundException(
                NotFoundException::PEEK_STATUS_MSG,
                NotFoundException::PEEK_STATUS_CODE,
            ));

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
            'status' => 'buried',
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertSame("No jobs found in 'buried' status.\n", $output);
    }
}
