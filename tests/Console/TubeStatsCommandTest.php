<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\StatsService;
use PHPUnit\Framework\MockObject\MockObject;

class TubeStatsCommandTest extends ConsoleTestCase
{
    private const STATS_TUBE = [
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
        'cmd-pause-tube' => 3,
        'pause' => 53,
        'pause-time-left' => 7,
    ];

    /**
     * @var StatsService|MockObject
     */
    private MockObject $statsService;

    protected function setUpCommand(): AbstractCommand
    {
        $this->statsService = $this->createMock(StatsService::class);
        $statsServiceFactory = fn () => $this->statsService;

        return new TubeStatsCommand($this->factory, $statsServiceFactory);
    }

    public function testAllStats(): void
    {
        $tube = sha1(uniqid());

        $this->statsService->expects(static::once())
            ->method('getTubeStats')
            ->with($tube)
            ->willReturn(self::STATS_TUBE);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
        ]);

        $output = $this->commandTester->getDisplay();

        // Table
        foreach (self::STATS_TUBE as $stat => $value) {
            static::assertMatchesRegularExpression("/{$stat}[\s|]+{$value}/", $output);
        }
    }

    /**
     * @dataProvider dataSingleStat
     */
    public function testSingleStat(string $stat, int $value): void
    {
        $tube = sha1(uniqid());

        $this->statsService->expects(static::once())
            ->method('getTubeStats')
            ->with($tube)
            ->willReturn(self::STATS_TUBE);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
            '--stat' => $stat,
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertSame($value . "\n", $output);
    }

    public function dataSingleStat(): iterable
    {
        foreach (self::STATS_TUBE as $stat => $value) {
            yield $stat => [$stat, $value];
        }
    }

    public function testSingleStatInvalid(): void
    {
        $tube = sha1(uniqid());
        $stat = sha1(uniqid());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Specified statistic '{$stat}' is not valid");

        $this->statsService->expects(static::once())
            ->method('getTubeStats')
            ->with($tube)
            ->willReturn(self::STATS_TUBE);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
            '--stat' => $stat,
        ]);
    }

    public function testTubeNameInvalid(): void
    {
        $tube = sha1(uniqid());

        $this->statsService->expects(static::once())
            ->method('getTubeStats')
            ->with($tube)
            ->willReturn([]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertSame("No statistics found for tube '{$tube}'.\n", $output);
    }
}
