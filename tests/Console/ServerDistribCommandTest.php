<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Console\Service\StatsService;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Factory;
use Phlib\Beanstalk\Pool;
use PHPUnit\Framework\MockObject\MockObject;

class ServerDistribCommandTest extends ConsoleTestCase
{
    /**
     * @var StatsService|MockObject
     */
    private MockObject $statsService;

    protected function setUpCommand(): AbstractCommand
    {
        // Override the Factory in ConsoleTestCase::setup() to control the connection per test
        $this->factory = $this->createMock(Factory::class);

        $this->statsService = $this->createMock(StatsService::class);
        $statsServiceFactory = fn() => $this->statsService;

        return new ServerDistribCommand($this->factory, $statsServiceFactory);
    }

    public function testErrorWithoutPool(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Command only works with a pool of beanstalk servers');

        // Use standard non-Pool connection
        $this->factory->method('createFromArray')
            ->willReturn($this->connection);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
        ]);
    }

    /**
     * @dataProvider dataStatsDisplay
     */
    public function testStatsDisplay(array $expectedStats, ?string $tubeName): void
    {
        $pool = $this->createMock(Pool::class);

        $this->factory->method('createFromArray')
            ->willReturn($pool);

        $statsMethod = 'stats';
        $statsArgs = [];
        $execInput = [
            'command' => $this->command->getName(),
        ];
        if (isset($tubeName)) {
            $statsMethod = 'statsTube';
            $statsArgs = [$tubeName];
            $execInput['tube'] = $tubeName;
        }

        $connections = [
            $this->createConnectionStats(array_keys($expectedStats), $statsMethod, $statsArgs),
            $this->createConnectionStats(array_keys($expectedStats), $statsMethod, $statsArgs),
        ];

        $pool->expects(static::once())
            ->method('getConnections')
            ->willReturn(array_column($connections, 'connection'));

        $this->commandTester->execute($execInput);

        $output = $this->commandTester->getDisplay();

        // Headers
        $expectedHeaders = '[\s|]+Stat[\s|]+';
        foreach ($connections as $details) {
            $expectedHeaders .= $details['connectionName'] . '[\s|]+';
        }
        static::assertMatchesRegularExpression('/' . $expectedHeaders . '/', $output);

        // Table
        foreach ($expectedStats as $statName => $statDisplay) {
            $expectedRow = '[\s|]+' . $statDisplay . '[\s|]+';
            foreach ($connections as $details) {
                $expectedRow .= $details['stats'][$statName] . '[\s|]+';
            }
            static::assertMatchesRegularExpression('/' . $expectedRow . '/', $output);
        }
    }

    public function dataStatsDisplay(): array
    {
        $baseStats = [
            'current-jobs-buried' => 'jobs-buried',
            'current-jobs-delayed' => 'jobs-delayed',
            'current-jobs-ready' => 'jobs-ready',
            'current-jobs-reserved' => 'jobs-reserved',
            'current-jobs-urgent' => 'jobs-urgent',
            'current-waiting' => 'workers-waiting',
        ];

        $allStats = array_merge($baseStats, [
            'current-workers' => 'workers-watching',
        ]);

        $tubeStats = array_merge($baseStats, [
            'current-watching' => 'workers-watching',
        ]);

        return [
            'all' => [$allStats, null],
            'tube' => [$tubeStats, sha1(uniqid('tube'))],
        ];
    }

    private function createConnectionStats(array $statNames, string $statsMethod, array $statsArgs): array
    {
        $connectionName = sha1(uniqid('name'));
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getName')
            ->willReturn($connectionName);

        $stats = [];
        foreach ($statNames as $statName) {
            $stats[$statName] = rand(1, 1024);
        }

        $connection->expects(static::once())
            ->method($statsMethod)
            ->with(...$statsArgs)
            ->willReturn($stats);

        return [
            'connectionName' => $connectionName,
            'connection' => $connection,
            'stats' => $stats,
        ];
    }
}
