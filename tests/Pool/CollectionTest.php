<?php

namespace Phlib\Tests\Pool;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Pool\Collection;
use Phlib\Beanstalk\Pool\SelectionStrategyInterface;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SelectionStrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $strategy;

    public function setUp()
    {
        $this->strategy = $this->getMock('\Phlib\Beanstalk\Pool\SelectionStrategyInterface');
    }

    public function tearDown()
    {
        $this->strategy = null;
    }

    public function testImplementsCollectionInterface()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Pool\CollectionInterface', new Collection([], $this->strategy));
    }

    public function testImplementsArrayAggregateInterface()
    {
        $this->assertInstanceOf('\IteratorAggregate', new Collection([], $this->strategy));
    }

    public function testArrayAggregateReturnsConnections()
    {
        $result = true;
        $collection = new Collection([$this->getMockConnection('id-123'), $this->getMockConnection('id-456')]);
        foreach ($collection as $connection) {
            $result = $result && ($connection instanceof Connection);
        }
        $this->assertTrue($result);
    }

    public function testArrayAggregateReturnsAllConnections()
    {
        $count = 0;
        $collection = new Collection([$this->getMockConnection('id-123'), $this->getMockConnection('id-456')]);
        foreach ($collection as $connection) {
            $count++;
        }
        $this->assertEquals(2, $count);
    }

    public function testDefaultStrategyIsRoundRobin()
    {
        $collection = new Collection([]);
        $this->assertInstanceOf('\Phlib\Beanstalk\Pool\RoundRobinStrategy', $collection->getSelectionStrategy());
    }

    public function testConstructorCanSetTheStrategy()
    {
        $collection = new Collection([], $this->strategy);
        $this->assertSame($this->strategy, $collection->getSelectionStrategy());
    }

    public function testConstructorTakesListOfValidConnections()
    {
        $serverKey = 'my-unique-key';
        $connection = $this->getMockConnection($serverKey);
        $collection = new Collection([$connection]);
        $this->assertSame($connection, $collection->getConnection($serverKey));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testConstructorChecksForValidConnections()
    {
        new Collection(['sdfdsf']);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testForUnknownConnection()
    {
        $collection = new Collection([$this->getMockConnection('id-123')]);
        $collection->getConnection('foo-bar');
    }

    public function testCanSendToExactConnection()
    {
        $identifier = 'id-123';
        $command    = 'stats';
        $connection = $this->getMockConnection($identifier);
        $connection->expects($this->once())
            ->method($command);
        $collection = new Collection([
            $connection,
            $this->getMockConnection('id-456')
        ]);
        $collection->sendToExact($identifier, $command);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\RuntimeException
     */
    public function testSendToExactOnError()
    {
        $identifier = 'id-123';
        $command    = 'stats';
        $connection = $this->getMockConnection($identifier);
        $connection->expects($this->any())
            ->method($command)
            ->will($this->throwException(new RuntimeException()));
        $collection = new Collection([$connection]);
        $collection->sendToExact($identifier, $command);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\RuntimeException
     * @expectedExceptionMessage Connection recently failed.
     */
    public function testGetConnectionThatHasErrored()
    {
        $identifier = 'id-123';
        $command    = 'stats';
        $connection = $this->getMockConnection($identifier);
        $connection->expects($this->any())
            ->method($command)
            ->will($this->throwException(new RuntimeException()));
        $collection = new Collection([$connection]);
        try {
            $collection->sendToExact($identifier, $command);
        } catch (\Exception $e) {
            // void
        }
        $collection->getConnection($identifier);
    }

    public function testGetConnectionThatHasErroredButIsDueRetry()
    {
        $identifier = 'id-123';
        $command    = 'stats';
        $connection = $this->getMockConnection($identifier);
        $connection->expects($this->any())
            ->method($command)
            ->will($this->throwException(new RuntimeException()));
        $collection = new Collection([$connection], $this->strategy, ['retry_delay' => 0]);
        try {
            $collection->sendToExact($identifier, $command);
        } catch (\Exception $e) {
            // void
        }
        $this->assertSame($connection, $collection->getConnection($identifier));
    }

    public function testSendToAllConnections()
    {
        $command    = 'stats';
        $calls      = 0;
        $callback   = function () use (&$calls) {
            $calls++;
        };

        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects($this->any())
            ->method($command)
            ->will($this->returnCallback($callback));

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects($this->any())
            ->method($command)
            ->will($this->returnCallback($callback));

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command);
        $this->assertEquals(2, $calls);
    }

    public function testSendToAllIgnoreErrors()
    {
        $command     = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects($this->once())
            ->method($command);

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects($this->any())
            ->method($command)
            ->will($this->throwException(new RuntimeException()));

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command);
    }

    public function testSendToAllIgnoreErrorsOfNotFound()
    {
        $command     = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects($this->once())
            ->method($command);

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects($this->any())
            ->method($command)
            ->will($this->throwException(new NotFoundException()));

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command);
    }

    public function testSendToAllCallsSuccessCallback()
    {
        $command     = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects($this->any())
            ->method($command);

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects($this->any())
            ->method($command);

        $called = 0;
        $onSuccess = function () use (&$called) {
            $called++;
        };

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command, [], $onSuccess);
        $this->assertEquals(2, $called);
    }

    public function testSendToAllCallsFailureCallback()
    {
        $command     = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects($this->any())
            ->method($command)
            ->will($this->throwException(new RuntimeException()));

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects($this->any())
            ->method($command)
            ->will($this->throwException(new RuntimeException()));

        $failed = 0;
        $onFailure = function () use (&$failed) {
            $failed++;
        };

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command, [], null, $onFailure);
        $this->assertEquals(2, $failed);
    }

    public function testSendToOne()
    {
        $command     = 'stats';
        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects($this->once())
            ->method($command);

        $connection2 = $this->getMockConnection('id-456');

        $this->strategy->expects($this->any())
            ->method('pickOne')
            ->will($this->returnValue($identifier1));

        $collection = new Collection([$connection1, $connection2], $this->strategy);
        $collection->sendToOne($command);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\RuntimeException
     */
    public function testSendToOneWhenAllConnectionsAreUsed()
    {
        $command     = 'stats';
        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects($this->any())
            ->method($command)
            ->will($this->returnValue(false));

        $identifier2 = 'id-456';
        $connection2 = $this->getMockConnection($identifier2);
        $connection2->expects($this->any())
            ->method($command)
            ->will($this->returnValue(false));

        $this->strategy->expects($this->any())
            ->method('pickOne')
            ->will($this->onConsecutiveCalls($identifier1, $identifier2));

        $collection = new Collection([$connection1, $connection2], $this->strategy);
        $collection->sendToOne($command);
    }

    public function testSendToOneIgnoresErrors()
    {
        $command     = 'stats';
        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects($this->any())
            ->method($command)
            ->will($this->throwException(new RuntimeException()));

        $identifier2 = 'id-456';
        $connection2 = $this->getMockConnection($identifier2);
        $connection2->expects($this->once())
            ->method($command)
            ->will($this->returnValue(234));

        $this->strategy->expects($this->any())
            ->method('pickOne')
            ->will($this->onConsecutiveCalls($identifier1, $identifier2));

        $collection = new Collection([$connection1, $connection2], $this->strategy);
        $collection->sendToOne($command);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\RuntimeException
     */
    public function testSendToOneThrowsTheLastError()
    {
        $command     = 'stats';
        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects($this->any())
            ->method($command)
            ->will($this->throwException(new RuntimeException()));

        $identifier2 = 'id-456';
        $connection2 = $this->getMockConnection($identifier2);
        $connection2->expects($this->once())
            ->method($command)
            ->will($this->throwException(new RuntimeException()));

        $this->strategy->expects($this->any())
            ->method('pickOne')
            ->will($this->onConsecutiveCalls($identifier1, $identifier2));

        $collection = new Collection([$connection1, $connection2], $this->strategy);
        $collection->sendToOne($command);
    }

    /**
     * @param mixed $identifier
     * @param array $methods
     * @return Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockConnection($identifier, array $methods = null)
    {
        $builder = $this->getMockBuilder('\Phlib\Beanstalk\Connection')
            ->disableOriginalConstructor();
        if ($methods) {
            $builder->setMethods($methods);
        }
        $connection = $builder->getMock();
        $connection->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($identifier));
        return $connection;
    }
}
