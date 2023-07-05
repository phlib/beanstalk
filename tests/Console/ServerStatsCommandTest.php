<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\StatsService;
use PHPUnit\Framework\MockObject\MockObject;

class ServerStatsCommandTest extends ConsoleTestCase
{
    private const STATS_INFO = [
        'job-timeouts' => 3,
        'total-jobs' => 59223137,
        'max-job-size' => 65535,
        'total-connections' => 154847176,
        'pid' => 912,
        'version' => '"1.12"',
        'rusage-utime' => 326.282258,
        'rusage-stime' => 1082.901991,
        'uptime' => 440851,
        'id' => '541ced2bff508923',
        'hostname' => 'test-host.local',
    ];

    private const STATS_BINLOG = [
        'binlog-current-index' => 75957,
        'binlog-max-size' => 10485760,
        'binlog-oldest-index' => 89653,
        'binlog-records-migrated' => 961582,
        'binlog-records-written' => 123149633,
    ];

    private const STATS_COMMAND = [
        'cmd-bury' => 5,
        'cmd-delete' => 59223282,
        'cmd-ignore' => 3409,
        'cmd-kick' => 2,
        'cmd-list-tube-used' => 0,
        'cmd-list-tubes' => 1022,
        'cmd-list-tubes-watched' => 0,
        'cmd-pause-tube' => 0,
        'cmd-peek' => 0,
        'cmd-peek-buried' => 6,
        'cmd-peek-delayed' => 0,
        'cmd-peek-ready' => 190,
        'cmd-put' => 59223137,
        'cmd-release' => 3741631,
        'cmd-reserve' => 54,
        'cmd-reserve-with-timeout' => 42152564796,
        'cmd-stats' => 468788,
        'cmd-stats-job' => 3764656,
        'cmd-stats-tube' => 531699,
        'cmd-touch' => 14226,
        'cmd-use' => 177313762,
        'cmd-watch' => 3409,
    ];

    private const STATS_CURRENT = [
        'current-connections' => 465,
        'current-jobs-buried' => 0,
        'current-jobs-delayed' => 69,
        'current-jobs-ready' => 1,
        'current-jobs-reserved' => 0,
        'current-jobs-urgent' => 0,
        'current-producers' => 184,
        'current-tubes' => 54,
        'current-waiting' => 0,
        'current-workers' => 462,
    ];

    /**
     * @var StatsService|MockObject
     */
    private MockObject $statsService;

    protected function setUpCommand(): AbstractCommand
    {
        $command = new ServerStatsCommand($this->factory);

        $this->statsService = $this->createMock(StatsService::class);
        $command->setStatsService($this->statsService);

        return $command;
    }

    public function testAllStats(): void
    {
        $this->statsService->expects(static::once())
            ->method('getServerInfo')
            ->willReturn(self::STATS_INFO);

        $this->statsService->expects(static::exactly(3))
            ->method('getServerStats')
            ->willReturnMap([
                [StatsService::SERVER_BINLOG, self::STATS_BINLOG],
                [StatsService::SERVER_COMMAND, self::STATS_COMMAND],
                [StatsService::SERVER_CURRENT, self::STATS_CURRENT],
            ]);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
        ]);

        $output = $this->commandTester->getDisplay();

        // Headers
        static::assertStringContainsString(
            'Host: ' . self::STATS_INFO['hostname'] . ' (pid ' . self::STATS_INFO['pid'] . ')',
            $output
        );
        static::assertStringContainsString(
            'Beanstalk Version: ' . self::STATS_INFO['version'],
            $output
        );
        static::assertStringContainsString(
            'Resources: uptime/' . self::STATS_INFO['uptime'] . ', connections/' . self::STATS_INFO['total-connections'],
            $output
        );
        static::assertStringContainsString(
            'Jobs: total/' . self::STATS_INFO['total-jobs'] . ', timeouts/' . self::STATS_INFO['job-timeouts'],
            $output
        );

        // Table
        $rows = array_merge(self::STATS_BINLOG, self::STATS_COMMAND, self::STATS_CURRENT);
        foreach ($rows as $stat => $value) {
            static::assertMatchesRegularExpression("/{$stat}\s+{$value}/", $output);
        }
    }

    /**
     * @dataProvider dataSingleStat
     */
    public function testSingleStat(string $stat, int $value): void
    {
        $this->statsService->expects(static::once())
            ->method('getServerInfo')
            ->willReturn(self::STATS_INFO);

        $this->statsService->expects(static::once())
            ->method('getServerStats')
            ->with(StatsService::SERVER_ALL)
            ->willReturn(array_merge(
                self::STATS_BINLOG,
                self::STATS_COMMAND,
                self::STATS_CURRENT,
            ));

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--stat' => $stat,
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertSame($value . "\n", $output);
    }

    public function dataSingleStat(): iterable
    {
        $stats = array_merge(
            self::STATS_BINLOG,
            self::STATS_COMMAND,
            self::STATS_CURRENT,
        );

        foreach ($stats as $stat => $value) {
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
            ->method('getServerInfo')
            ->willReturn(self::STATS_INFO);

        $this->statsService->expects(static::once())
            ->method('getServerStats')
            ->with(StatsService::SERVER_ALL)
            ->willReturn(array_merge(
                self::STATS_BINLOG,
                self::STATS_COMMAND,
                self::STATS_CURRENT,
            ));

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--stat' => $stat,
        ]);
    }
}
