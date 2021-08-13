<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Pool\RandomStrategy;
use Phlib\Beanstalk\Pool\RoundRobinStrategy;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testCreate(): void
    {
        static::assertInstanceOf(ConnectionInterface::class, Factory::create('localhost'));
    }

    /**
     * @dataProvider createFromArrayDataProvider
     */
    public function testCreateFromArray($expectedClass, $config): void
    {
        static::assertInstanceOf($expectedClass, Factory::createFromArray($config));
    }

    public function createFromArrayDataProvider(): array
    {
        $connectionClass = Connection::class;
        $poolClass = Pool::class;
        $defaultHost = [
            'host' => 'localhost',
        ];

        return [
            [
                $connectionClass,
                $defaultHost,
            ],
            [
                $connectionClass,
                [
                    'host' => 'localhost',
                    'port' => 123456,
                ],
            ],
            [
                $connectionClass,
                [
                    'server' => $defaultHost,
                ],
            ],
            [
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
        $pool = Factory::createFromArray($poolConfig);
        /* @var $pool Pool */

        $collection = $pool->getCollection();
        /* @var $collection Pool\Collection */

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
        Factory::createFromArray($poolConfig);
    }

    public function testCreateFromArrayFailsWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Factory::createFromArray([]);
    }

    public function testCreateConnections(): void
    {
        $result = true;
        $config = [
            'host' => 'locahost',
        ];

        $connections = Factory::createConnections([$config, $config, $config]);
        foreach ($connections as $connection) {
            $result = $result && $connection instanceof Connection;
        }

        static::assertTrue($result);
    }
}
