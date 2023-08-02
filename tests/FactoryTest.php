<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Exception\InvalidArgumentException;
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
        return [
            'local' => [
                Connection::class,
                [
                    'host' => 'localhost',
                ],
            ],
            'localWithPort' => [
                Connection::class,
                [
                    'host' => 'localhost',
                    'port' => 123456,
                ],
            ],
            'pool' => [
                Pool::class,
                [
                    ['host' => 'localhost1'],
                    ['host' => 'localhost2'],
                ],
            ],
        ];
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
