<?php

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Factory;
use Phlib\Beanstalk\Pool;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $this->assertInstanceOf(ConnectionInterface::class, Factory::create('localhost'));
    }

    /**
     * @dataProvider createFromArrayDataProvider
     */
    public function testCreateFromArray($expectedClass, $config)
    {
        $this->assertInstanceOf($expectedClass, Factory::createFromArray($config));
    }

    public function createFromArrayDataProvider()
    {
        $connectionClass = Connection::class;
        $poolClass       = Pool::class;

        return [
            [$connectionClass, ['host' => 'localhost']],
            [$connectionClass, ['host' => 'localhost', 'port' => 123456]],
            [$poolClass, [['host' => 'localhost1'], ['host' => 'localhost2']]],
            [$poolClass, ['conn1' => ['host' => 'localhost1'], 'conn2' => ['host' => 'localhost2']]]
        ];
    }

    public function testCreateFromArrayFailsWhenEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        Factory::createFromArray([]);
    }

    public function testCreateConnections()
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
