<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console\Service;

use Phlib\Beanstalk\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatsServiceTest extends TestCase
{
    private const STATS_RAW = [
        'current-jobs-urgent' => 0,
        'current-jobs-ready' => 1,
        'current-jobs-reserved' => 0,
        'current-jobs-delayed' => 69,
        'current-jobs-buried' => 0,
        'cmd-put' => 59223137,
        'cmd-peek' => 0,
        'cmd-peek-ready' => 190,
        'cmd-peek-delayed' => 0,
        'cmd-peek-buried' => 6,
        'cmd-reserve' => 54,
        'cmd-reserve-with-timeout' => 42152564796,
        'cmd-delete' => 59223282,
        'cmd-release' => 3741631,
        'cmd-use' => 177313762,
        'cmd-watch' => 3409,
        'cmd-ignore' => 3409,
        'cmd-bury' => 5,
        'cmd-kick' => 2,
        'cmd-touch' => 14226,
        'cmd-stats' => 468788,
        'cmd-stats-job' => 3764656,
        'cmd-stats-tube' => 531699,
        'cmd-list-tubes' => 1022,
        'cmd-list-tube-used' => 0,
        'cmd-list-tubes-watched' => 0,
        'cmd-pause-tube' => 0,
        'job-timeouts' => 3,
        'total-jobs' => 59223137,
        'max-job-size' => 65535,
        'current-tubes' => 54,
        'current-connections' => 465,
        'current-producers' => 184,
        'current-workers' => 462,
        'current-waiting' => 0,
        'total-connections' => 154847176,
        'pid' => 912,
        'version' => '"1.12"',
        'rusage-utime' => 326.282258,
        'rusage-stime' => 1082.901991,
        'uptime' => 440851,
        'binlog-oldest-index' => 89653,
        'binlog-current-index' => 75957,
        'binlog-records-migrated' => 0,
        'binlog-records-written' => 464,
        'binlog-max-size' => 10485760,
        'draining' => 'false',
        'id' => '541ced2bff508923',
        'hostname' => 'test-host.local',
        'os' => '#1 SMP Debian 4.19.194-2 (2021-06-21)',
        'platform' => 'x86_64',
    ];

    private const STATS_KEY_INFO = [
        'job-timeouts',
        'total-jobs',
        'max-job-size',
        'total-connections',
        'pid',
        'version',
        'rusage-utime',
        'rusage-stime',
        'uptime',
        'id',
        'hostname',
    ];

    private const STATS_KEY_BINLOG = [
        'binlog-current-index',
        'binlog-max-size',
        'binlog-oldest-index',
        'binlog-records-migrated',
        'binlog-records-written',
    ];

    private const STATS_KEY_COMMAND = [
        'cmd-bury',
        'cmd-delete',
        'cmd-ignore',
        'cmd-kick',
        'cmd-list-tube-used',
        'cmd-list-tubes',
        'cmd-list-tubes-watched',
        'cmd-pause-tube',
        'cmd-peek',
        'cmd-peek-buried',
        'cmd-peek-delayed',
        'cmd-peek-ready',
        'cmd-put',
        'cmd-release',
        'cmd-reserve',
        'cmd-reserve-with-timeout',
        'cmd-stats',
        'cmd-stats-job',
        'cmd-stats-tube',
        'cmd-touch',
        'cmd-use',
        'cmd-watch',
    ];

    private const STATS_KEY_CURRENT = [
        'current-connections',
        'current-jobs-buried',
        'current-jobs-delayed',
        'current-jobs-ready',
        'current-jobs-reserved',
        'current-jobs-urgent',
        'current-producers',
        'current-tubes',
        'current-waiting',
        'current-workers',
    ];

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
     * @var Connection|MockObject
     */
    private Connection $connection;

    private StatsService $statsService;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->expects(static::any())
            ->method('getName')
            ->willReturn(sha1(uniqid()));

        $this->statsService = new StatsService($this->connection);

        parent::setUp();
    }

    public function testGetServerInfo(): void
    {
        $this->connection->expects(static::once())
            ->method('stats')
            ->willReturn(self::STATS_RAW);

        $expectedKeys = self::STATS_KEY_INFO;
        $expected = array_intersect_key(self::STATS_RAW, array_flip($expectedKeys));

        $actual = $this->statsService->getServerInfo();
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider dataGetServerStats
     */
    public function testGetServerStats(int $filter, array $expectedKeys): void
    {
        $this->connection->expects(static::once())
            ->method('stats')
            ->willReturn(self::STATS_RAW);

        $expected = array_intersect_key(self::STATS_RAW, array_flip($expectedKeys));

        $actual = $this->statsService->getServerStats($filter);
        self::assertSame($expected, $actual);
    }

    public function dataGetServerStats(): array
    {
        return [
            'binlog' => [StatsService::SERVER_BINLOG, self::STATS_KEY_BINLOG],
            'command' => [StatsService::SERVER_COMMAND, self::STATS_KEY_COMMAND],
            'current' => [StatsService::SERVER_CURRENT, self::STATS_KEY_CURRENT],
            'all' => [StatsService::SERVER_ALL, array_merge(self::STATS_KEY_BINLOG, self::STATS_KEY_COMMAND, self::STATS_KEY_CURRENT)],
        ];
    }

    public function testGetTubeStats(): void
    {
        $tube = sha1(uniqid());
        $stats = array_merge(['name' => $tube], self::STATS_TUBE);

        $this->connection->expects(static::once())
            ->method('statsTube')
            ->with($tube)
            ->willReturn($stats);

        $actual = $this->statsService->getTubeStats($tube);
        self::assertSame(self::STATS_TUBE, $actual);
    }

    public function testGetAllTubeStats(): void
    {
        // Tube names in non-alpha order
        $tube = sha1(uniqid());
        $tubes = [
            $tube . 'C',
            $tube . 'A',
            $tube . 'B',
        ];

        $tubeStatsParams = [];
        $tubeStats = [];
        $expected = [];
        foreach ($tubes as $name) {
            $tubeStatsParams[] = [$name];

            // Create random stats so each tube has different results
            $stats = self::STATS_TUBE;
            foreach ($stats as &$stat) {
                $stat = rand();
            }
            $tubeStats[$name] = array_merge(['name' => $name], $stats);

            // Expected results don't have 'pause' entries
            unset(
                $stats['cmd-pause-tube'],
                $stats['pause'],
                $stats['pause-time-left'],
            );
            $expected[$name] = array_merge(['name' => $name], $stats);
        }

        // Expected results in alphabetical order, keys no longer needed
        ksort($expected);
        $expected = array_values($expected);
        $tubeStats = array_values($tubeStats);

        $this->connection->expects(static::once())
            ->method('listTubes')
            ->willReturn($tubes);

        $this->connection->expects(static::exactly(count($tubes)))
            ->method('statsTube')
            ->withConsecutive(...$tubeStatsParams)
            ->willReturnOnConsecutiveCalls(...$tubeStats);

        $actual = $this->statsService->getAllTubeStats();
        self::assertSame($expected, $actual);
    }
}
