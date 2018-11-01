<?php
declare(strict_types=1);

namespace Phlib\Tests\Pool;

use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Pool\ManagedConnection;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ManagedConnectionTest extends TestCase
{
    use PHPMock;

    /**
     * @var ConnectionInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $connection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $time;

    public function setUp()
    {
        parent::setUp();
        $this->connection = $this->prophesize(ConnectionInterface::class);
        $this->time = $this->getFunctionMock('\Phlib\Beanstalk\Pool', 'time');
    }

    public function testSetGetConnection(): void
    {
        $connection = $this->connection->reveal();
        $managed = new ManagedConnection($connection);
        $this->assertSame($connection, $managed->getConnection());
    }

    public function testByDefaultConnectionIsAvailable(): void
    {
        $this->assertTrue((new ManagedConnection($this->connection->reveal()))->isAvailable());
    }

    public function testNotAvailableAfterError(): void
    {
        $this->connection->watch(Argument::any())->willThrow(new RuntimeException());
        $managed = new ManagedConnection($this->connection->reveal());
        try {
            $managed->send('watch', 'tube');
        } catch (RuntimeException $e) {
            // ignore
        }
        $this->assertFalse($managed->isAvailable());
    }

    public function testNotAvailableBecomesAvailable(): void
    {
        $initialTime = 100;
        $retryDelay  = 600;

        $this->time->expects($this->at(0))->willReturn($initialTime);
        $this->time->expects($this->at(1))->willReturn($initialTime + $retryDelay);

        $this->connection->watch(Argument::any())->willThrow(new RuntimeException());
        $managed = new ManagedConnection($this->connection->reveal(), $retryDelay);
        try {
            $managed->send('watch', 'tube');
        } catch (RuntimeException $e) {
            // ignore
        }
        $this->assertTrue($managed->isAvailable());
    }

    public function testConnectionGetsRestored(): void
    {
        $watchCalls  = 0;
        $ignoreCalls = 0;
        $connection = $this->connection;

        $this->connection->useTube(Argument::any());
        $this->connection->watch(Argument::any())->will(function () use (&$watchCalls, $connection) {
            $watchCalls++;
            return $connection;
        });
        $this->connection->ignore(Argument::any())->will(function () use (&$ignoreCalls, $connection) {
            $ignoreCalls++;
            return $connection;
        });
        $this->connection->release(Argument::any())->will(function () use (&$watchCalls, $connection) {
            if ($watchCalls == 1) {
                throw new RuntimeException();
            }
            return $connection;
        });

        $managed = new ManagedConnection($this->connection->reveal(), 0);
        $managed->send('watch', 'TubeOne');
        $managed->send('ignore', 'default');
        try {
            $managed->send('release', 'JobId1');
        } catch (RuntimeException $e) {
            // ignore
        }
        $managed->send('release', 'JobId2');

        $this->assertEquals(4, $watchCalls + $ignoreCalls);
    }
}
