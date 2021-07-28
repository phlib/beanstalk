<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\NotFoundException;

class JobPeekCommandTest extends ConsoleTestCase
{
    protected function setUp(): void
    {
        $this->command = new JobPeekCommand();

        parent::setUp();
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
        $this->expectException(NotFoundException::class);

        $jobId = rand();

        $this->connection->expects(static::once())
            ->method('peek')
            ->with($jobId)
            ->willThrowException(new NotFoundException('job not found'));

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'job-id' => $jobId,
        ]);
    }
}
