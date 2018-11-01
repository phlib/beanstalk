<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Factory;
use Phlib\Beanstalk\Pool;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $this->assertInstanceOf(ConnectionInterface::class, Factory::create('localhost'));
    }

    /**
     * @param string $expectedClass
     * @param array $config
     * @dataProvider createFromArrayDataProvider
     */
    public function testCreateFromArray(string $expectedClass, array $config): void
    {
        $this->assertInstanceOf($expectedClass, Factory::createFromArray($config));
    }

    public function createFromArrayDataProvider(): array
    {
        $connectionClass = Connection::class;
        $poolClass       = Pool::class;

        return [
            'local' => [$connectionClass, ['host' => 'localhost']],
            'localWithPort' => [$connectionClass, ['host' => 'localhost', 'port' => 123456]],
            'poolHosts' => [$poolClass, [['host' => 'localhost1'], ['host' => 'localhost2']]],
            'namedPool' => [$poolClass, ['conn1' => ['host' => 'localhost1'], 'conn2' => ['host' => 'localhost2']]]
        ];
    }

    public function testCreateFromArrayFailsWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Factory::createFromArray([]);
    }

    public function testCreateConnections(): void
    {
        $result = true;
        $config = ['host' => 'locahost'];

        $connections = Factory::createConnections([$config, $config, $config]);
        foreach ($connections as $connection) {
            $result = $result && $connection instanceof Connection;
        }

        $this->assertTrue($result);
    }
}
