<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\NotFoundException;

class JobStatsCommandTest extends ConsoleTestCase
{
    protected function setUpCommand(): AbstractCommand
    {
        return new JobStatsCommand($this->factory);
    }

    public function testJobStats(): void
    {
        $jobId = rand();

        $stats = [
            'id' => $jobId,
            'tube' => 'default',
            'state' => 'ready',
            'pri' => 1024,
            'age' => 62,
            'delay' => 20,
            'ttr' => 60,
            'time-left' => 10,
            'file' => 36,
            'reserves' => 5,
            'timeouts' => 2,
            'releases' => 1,
            'buries' => 3,
            'kicks' => 4,
        ];

        $this->connection->expects(static::once())
            ->method('statsJob')
            ->with($jobId)
            ->willReturn($stats);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'job-id' => $jobId,
        ]);
        $currentTime = time(); // Time at execution

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString("Job ID: {$jobId}", $output);

        // Headers
        static::assertMatchesRegularExpression("/tube\s+{$stats['tube']}/", $output);
        static::assertMatchesRegularExpression("/state\s+{$stats['state']}/", $output);

        // Table
        $rows = $stats;
        unset($rows['id'], $rows['tube'], $rows['state']);
        $rows['created'] = date('Y-m-d H:i:s', $currentTime - $stats['age']);
        $rows['scheduled'] = date('Y-m-d H:i:s', $currentTime - $stats['age'] + $stats['delay']);
        foreach ($rows as $stat => $value) {
            static::assertMatchesRegularExpression("/{$stat}[\s|]+{$value}/", $output);
        }
    }

    public function testJobNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $jobId = rand();

        $this->connection->expects(static::once())
            ->method('statsJob')
            ->with($jobId)
            ->willThrowException(new NotFoundException('job not found'));

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'job-id' => $jobId,
        ]);
    }
}
