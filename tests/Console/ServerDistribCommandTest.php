<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Factory;
use Phlib\Beanstalk\Pool;
use Phlib\Beanstalk\Pool\Collection;
use Phlib\Beanstalk\StatsService;
use PHPUnit\Framework\MockObject\MockObject;

class ServerDistribCommandTest extends ConsoleTestCase
{
    private const STAT_MAP = [
        'current-jobs-buried' => 'jobs-buried',
        'current-jobs-delayed' => 'jobs-delayed',
        'current-jobs-ready' => 'jobs-ready',
        'current-jobs-reserved' => 'jobs-reserved',
        'current-jobs-urgent' => 'jobs-urgent',
        'current-waiting' => 'workers-waiting',
        'current-watching' => 'workers-watching',
        'current-workers' => 'workers-watching',
    ];

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
        $this->factory->method('createFromArrayBC')
            ->willReturn($this->connection);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
        ]);
    }

    public function testAllStats(): void
    {
        $pool = $this->createMock(Pool::class);

        $this->factory->method('createFromArrayBC')
            ->willReturn($pool);

        $connections = [
            $this->createConnectionStats(),
            $this->createConnectionStats(),
        ];

        $collection = $this->createMock(Collection::class);
        $collection->expects(static::once())
            ->method('sendToAll')
            ->with('stats', [])
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

        $this->commandTester->execute([
            'command' => $this->command->getName(),
        ]);

        $output = $this->commandTester->getDisplay();

        // Headers
        $expectedHeaders = '[\s|]+Stat[\s|]+';
        foreach ($connections as $details) {
            $expectedHeaders .= $details['connectionName'] . '[\s|]+';
        }
        static::assertMatchesRegularExpression('/' . $expectedHeaders . '/', $output);

        // Table; doesn't use `current-watching` for all stats
        $expectedStats = self::STAT_MAP;
        unset($expectedStats['current-watching']);

        foreach ($expectedStats as $statName => $statDisplay) {
            $expectedRow = '[\s|]+' . $statDisplay . '[\s|]+';
            foreach ($connections as $details) {
                $expectedRow .= $details['stats'][$statName] . '[\s|]+';
            }
            static::assertMatchesRegularExpression('/' . $expectedRow . '/', $output);
        }
    }

    public function testSingleTube(): void
    {
        $tubeName = sha1(uniqid('tube'));

        $pool = $this->createMock(Pool::class);

        $this->factory->method('createFromArrayBC')
            ->willReturn($pool);

        $connections = [
            $this->createConnectionStats(),
            $this->createConnectionStats(),
        ];

        $collection = $this->createMock(Collection::class);
        $collection->expects(static::once())
            ->method('sendToAll')
            ->with('statsTube', [$tubeName])
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

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tubeName,
        ]);

        $output = $this->commandTester->getDisplay();

        // Headers
        $expectedHeaders = '[\s|]+Stat[\s|]+';
        foreach ($connections as $details) {
            $expectedHeaders .= $details['connectionName'] . '[\s|]+';
        }
        static::assertMatchesRegularExpression('/' . $expectedHeaders . '/', $output);

        // Table; doesn't use `current-workers` for tube stats
        $expectedStats = self::STAT_MAP;
        unset($expectedStats['current-workers']);

        foreach ($expectedStats as $statName => $statDisplay) {
            $expectedRow = '[\s|]+' . $statDisplay . '[\s|]+';
            foreach ($connections as $details) {
                $expectedRow .= $details['stats'][$statName] . '[\s|]+';
            }
            static::assertMatchesRegularExpression('/' . $expectedRow . '/', $output);
        }
    }

    private function createConnectionStats(): array
    {
        $connectionName = sha1(uniqid('name'));
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getName')
            ->willReturn($connectionName);

        $stats = [];
        foreach (self::STAT_MAP as $statName => $statDisplay) {
            $stats[$statName] = rand(1, 1024);
        }

        return [
            'connectionName' => $connectionName,
            'connection' => $connection,
            'stats' => $stats,
        ];
    }
}
