<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Factory;
use Phlib\Beanstalk\Pool;
use Phlib\Beanstalk\Pool\Collection;
use Phlib\Beanstalk\Stats\Service as StatsService;
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
        $statsServiceFactory = fn () => $this->statsService;

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

        $connections = [
            $this->createConnectionStats(array_keys($expectedStats)),
            $this->createConnectionStats(array_keys($expectedStats)),
        ];

        $method = 'stats';
        $args = [];
        if (isset($tubeName)) {
            $method = 'statsTube';
            $args = [$tubeName];
        }

        $collection = $this->createMock(Collection::class);
        $collection->expects(static::once())
            ->method('sendToAll')
            ->with($method, $args)
            ->willReturnCallback(function ($command, $arguments, $success) use ($connections) {
                foreach ($connections as $details) {
                    call_user_func($success, [
                        'connection' => $details['connection'],
                        'response' => $details['stats'],
                    ]);
                }
            });

        $pool->expects(static::once())
            ->method('getCollection')
            ->willReturn($collection);

        $execInput = [
            'command' => $this->command->getName(),
        ];
        if (isset($tubeName)) {
            $execInput['tube'] = $tubeName;
        }
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

    private function createConnectionStats(array $statNames): array
    {
        $connectionName = sha1(uniqid('name'));
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getName')
            ->willReturn($connectionName);

        $stats = [];
        foreach ($statNames as $statName) {
            $stats[$statName] = rand(1, 1024);
        }

        return [
            'connectionName' => $connectionName,
            'connection' => $connection,
            'stats' => $stats,
        ];
    }
}
