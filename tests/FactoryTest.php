<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Pool\RandomStrategy;
use Phlib\Beanstalk\Pool\RoundRobinStrategy;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new Factory();
        static::assertInstanceOf(ConnectionInterface::class, $factory->create('localhost'));
    }

    /**
     * @dataProvider createFromArrayDataProvider
     */
    public function testCreateFromArray(string $expectedClass, array $config): void
    {
        $factory = new Factory();
        static::assertInstanceOf($expectedClass, $factory->createFromArray($config));
    }

    public function createFromArrayDataProvider(): array
    {
        $connectionClass = Connection::class;
        $poolClass = Pool::class;
        $defaultHost = [
            'host' => 'localhost',
        ];

        return [
            'local' => [
                $connectionClass,
                $defaultHost,
            ],
            'localWithPort' => [
                $connectionClass,
                [
                    'host' => 'localhost',
                    'port' => 123456,
                ],
            ],
            'localArray' => [
                $connectionClass,
                [
                    'server' => $defaultHost,
                ],
            ],
            'pool' => [
                $poolClass,
                [
                    'servers' => [$defaultHost, $defaultHost],
                ],
            ],
        ];
    }

    /**
     * @dataProvider creatingPoolUsesStrategyDataProvider
     */
    public function testCreatingPoolUsesStrategy(string $strategyClass): void
    {
        $hostConfig = [
            'host' => 'localhost',
        ];
        $poolConfig = [
            'servers' => [$hostConfig, $hostConfig],
            'strategyClass' => $strategyClass,
        ];

        $factory = new Factory();
        /** @var Pool $pool */
        $pool = $factory->createFromArray($poolConfig);

        /** @var Pool\Collection $collection */
        $collection = $pool->getCollection();

        static::assertInstanceOf($strategyClass, $collection->getSelectionStrategy());
    }

    public function creatingPoolUsesStrategyDataProvider(): array
    {
        return [
            [RoundRobinStrategy::class],
            [RandomStrategy::class],
        ];
    }

    public function testCreatingPoolFailsWithInvalidStrategyClass(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $hostConfig = [
            'host' => 'localhost',
        ];
        $poolConfig = [
            'servers' => [$hostConfig, $hostConfig],
            'strategyClass' => '\Some\RandomClass\ThatDoesnt\Exist',
        ];

        $factory = new Factory();
        $factory->createFromArray($poolConfig);
    }

    public function testCreateFromArrayFailsWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $factory = new Factory();
        $factory->createFromArray([]);
    }

    public function testCreateConnections(): void
    {
        $result = true;
        $config = [
            'host' => 'locahost',
        ];

        $factory = new Factory();
        $connections = $factory->createConnections([$config, $config, $config]);
        foreach ($connections as $connection) {
            $result = $result && $connection instanceof Connection;
        }

        static::assertTrue($result);
    }
}
