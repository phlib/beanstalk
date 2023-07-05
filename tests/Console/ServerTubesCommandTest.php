<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\StatsService;
use PHPUnit\Framework\MockObject\MockObject;

class ServerTubesCommandTest extends ConsoleTestCase
{
    private const STATS_TUBES = [
        [
            'name' => 'default',
            'current-jobs-urgent' => 0,
            'current-jobs-ready' => 0,
            'current-jobs-reserved' => 0,
            'current-jobs-delayed' => 0,
            'current-jobs-buried' => 0,
            'total-jobs' => 0,
            'current-using' => 1,
            'current-watching' => 1,
            'current-waiting' => 0,
            'cmd-delete' => 0,
        ],
        [
            'name' => 'test',
            'current-jobs-urgent' => 1,
            'current-jobs-ready' => 5,
            'current-jobs-reserved' => 2,
            'current-jobs-delayed' => 48,
            'current-jobs-buried' => 3,
            'total-jobs' => 3636440,
            'current-using' => 84,
            'current-watching' => 81,
            'current-waiting' => 6,
            'cmd-delete' => 3636434,
        ],
    ];

    /**
     * @var StatsService|MockObject
     */
    private MockObject $statsService;

    protected function setUpCommand(): AbstractCommand
    {
        $this->statsService = $this->createMock(StatsService::class);
        $statsServiceFactory = fn () => $this->statsService;

        return new ServerTubesCommand($this->factory, $statsServiceFactory);
    }

    public function testTubeStats(): void
    {
        $this->statsService->expects(static::once())
            ->method('getAllTubeStats')
            ->willReturn(self::STATS_TUBES);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
        ]);

        $output = $this->commandTester->getDisplay();

        // Headers
        $headers = StatsService::TUBE_HEADER_MAPPING;
        $pattern = '/' . implode('[\s|]+', $headers) . '/';
        static::assertMatchesRegularExpression($pattern, $output);

        // Table
        foreach (self::STATS_TUBES as $row) {
            $pattern = '/' . implode('[\s|]+', $row) . '/';
            static::assertMatchesRegularExpression($pattern, $output);
        }
    }

    public function testTubeStatsNone(): void
    {
        $this->statsService->expects(static::once())
            ->method('getAllTubeStats')
            ->willReturn([]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
        ]);

        $output = $this->commandTester->getDisplay();

        static::assertSame("No tubes found.\n", $output);
    }
}
