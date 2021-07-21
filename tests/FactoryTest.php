<?php

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Pool\RandomStrategy;
use Phlib\Beanstalk\Pool\RoundRobinStrategy;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testCreate()
    {
        static::assertInstanceOf(ConnectionInterface::class, Factory::create('localhost'));
    }

    /**
     * @dataProvider createFromArrayDataProvider
     */
    public function testCreateFromArray($expectedClass, $config)
    {
        static::assertInstanceOf(ConnectionInterface::class, Factory::create('localhost'));
    }

    public function createFromArrayDataProvider()
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
     * @param string $strategyClass
     * @dataProvider creatingPoolUsesStrategyDataProvider
     */
    public function testCreatingPoolUsesStrategy($strategyClass)
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

    public function creatingPoolUsesStrategyDataProvider()
    {
        return [
            [RoundRobinStrategy::class],
            [RandomStrategy::class],
        ];
    }

    public function testCreatingPoolFailsWithInvalidStrategyClass()
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

    public function testCreateFromArrayFailsWhenEmpty()
    {
        $this->expectException(InvalidArgumentException::class);

        Factory::createFromArray([]);
    }

    public function testCreateConnections()
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
