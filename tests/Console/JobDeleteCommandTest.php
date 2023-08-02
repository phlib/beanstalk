<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\NotFoundException;

class JobDeleteCommandTest extends ConsoleTestCase
{
    protected function setUpCommand(): AbstractCommand
    {
        return new JobDeleteCommand($this->factory);
    }

    public function testDeleteJob(): void
    {
        $jobId = rand();

        $this->connection->expects(static::once())
            ->method('delete')
            ->with($jobId)
            ->willReturnSelf();

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'job-id' => $jobId,
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString("Job '{$jobId}' successfully deleted", $output);
    }

    public function testJobNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $jobId = rand();

        $this->connection->expects(static::once())
            ->method('delete')
            ->with($jobId)
            ->willThrowException(new NotFoundException('job not found'));

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'job-id' => $jobId,
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertStringNotContainsString('successfully deleted', $output);
    }
}
