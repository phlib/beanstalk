<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\InvalidArgumentException;

class TubePeekCommandTest extends ConsoleTestCase
{
    protected function setUp(): void
    {
        $this->command = new TubePeekCommand();

        parent::setUp();
    }

    public function testTubePeekDefault(): void
    {
        $tube = sha1(uniqid());
        $jobId = rand();
        $body = sha1(uniqid());

        $this->connection->expects(static::once())
            ->method('useTube')
            ->with($tube)
            ->willReturnSelf();

        $this->connection->expects(static::once())
            ->method('peekBuried')
            ->willReturn([
                'id' => $jobId,
                'body' => $body,
            ]);

        // No '--status'
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString("Job ID: {$jobId}", $output);
        static::assertStringContainsString($body, $output);
    }

    public function testTubePeekBuried(): void
    {
        $tube = sha1(uniqid());
        $jobId = rand();
        $body = sha1(uniqid());

        $this->connection->expects(static::once())
            ->method('useTube')
            ->with($tube)
            ->willReturnSelf();

        $this->connection->expects(static::once())
            ->method('peekBuried')
            ->willReturn([
                'id' => $jobId,
                'body' => $body,
            ]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
            '--status' => 'buried',
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
            ->with($tube)
            ->willReturnSelf();

        $this->connection->expects(static::once())
            ->method('peekDelayed')
            ->willReturn([
                'id' => $jobId,
                'body' => $body,
            ]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
            '--status' => 'delayed',
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
            ->with($tube)
            ->willReturnSelf();

        $this->connection->expects(static::once())
            ->method('peekReady')
            ->willReturn([
                'id' => $jobId,
                'body' => $body,
            ]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
            '--status' => 'ready',
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
            '--status' => sha1(uniqid()),
        ]);
    }

    public function testTubePeekNoResult(): void
    {
        $tube = sha1(uniqid());

        $this->connection->expects(static::once())
            ->method('useTube')
            ->with($tube)
            ->willReturnSelf();

        $this->connection->expects(static::once())
            ->method('peekBuried')
            ->willReturn(false);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertSame("No jobs found in 'buried' status.\n", $output);
    }
}
