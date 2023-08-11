<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\NotFoundException;

class JobPeekCommandTest extends ConsoleTestCase
{
    protected function setUpCommand(): AbstractCommand
    {
        return new JobPeekCommand($this->factory);
    }

    public function testPeekJob(): void
    {
        $jobId = rand();
        $body = sha1(uniqid());

        $this->connection->expects(static::once())
            ->method('peek')
            ->with($jobId)
            ->willReturn([
                'id' => $jobId,
                'body' => $body,
            ]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'job-id' => $jobId,
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString("Job ID: {$jobId}", $output);
        static::assertStringContainsString($body, $output);
    }

    public function testJobNotFound(): void
    {
        $jobId = rand();
        $message = sha1(uniqid('xMsg'));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage($message);

        $this->connection->expects(static::once())
            ->method('peek')
            ->with($jobId)
            ->willThrowException(new NotFoundException($message));

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'job-id' => $jobId,
        ]);
    }
}
