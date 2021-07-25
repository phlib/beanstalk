<?php

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\RuntimeException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     * @var SelectionStrategyInterface|MockObject
     */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = $this->createMock(SelectionStrategyInterface::class);
    }

    protected function tearDown()
    {
        $this->strategy = null;
    }

    public function testImplementsCollectionInterface()
    {
        static::assertInstanceOf(CollectionInterface::class, new Collection([], $this->strategy));
    }

    public function testImplementsArrayAggregateInterface()
    {
        static::assertInstanceOf(\IteratorAggregate::class, new Collection([], $this->strategy));
    }

    public function testArrayAggregateReturnsConnections()
    {
        $result = true;
        $collection = new Collection([$this->getMockConnection('id-123'), $this->getMockConnection('id-456')]);
        foreach ($collection as $connection) {
            $result = $result && ($connection instanceof Connection);
        }
        static::assertTrue($result);
    }

    public function testArrayAggregateReturnsAllConnections()
    {
        $count = 0;
        $collection = new Collection([$this->getMockConnection('id-123'), $this->getMockConnection('id-456')]);
        foreach ($collection as $connection) {
            $count++;
        }
        static::assertEquals(2, $count);
    }

    public function testArrayAggregateSkipsErroredConnections(): void
    {
        $connection1 = $this->getMockConnection('id-123');

        // Make connection2 marked for retry
        $connection2 = $this->getMockConnection('id-456');
        $connection2->method('stats')
            ->willThrowException(new RuntimeException());

        $collection = new Collection([$connection1, $connection2]);

        // Make the collection send a command and get the RuntimeException
        $collection->sendToAll('stats');

        $count = 0;
        foreach ($collection as $connection) {
            $count++;
        }
        static::assertEquals(1, $count);
    }

    public function testGetAvailableKeys(): void
    {
        $key1 = 'id-123';
        $key2 = 'id-456';
        $collection = new Collection([$this->getMockConnection($key1), $this->getMockConnection($key2)]);
        $expected = [
            $key1,
            $key2,
        ];
        static::assertSame($expected, $collection->getAvailableKeys());
    }

    public function testGetAvailableKeysSkipsErroredConnections(): void
    {
        $key1 = 'id-123';
        $key2 = 'id-456';

        $connection1 = $this->getMockConnection($key1);

        // Make connection2 marked for retry
        $connection2 = $this->getMockConnection($key2);
        $connection2->method('stats')
            ->willThrowException(new RuntimeException());

        $collection = new Collection([$connection1, $connection2]);

        // Make the collection send a command and get the RuntimeException
        $collection->sendToAll('stats');

        $expected = [
            $key1,
        ];
        static::assertSame($expected, $collection->getAvailableKeys());
    }

    public function testDefaultStrategyIsRoundRobin()
    {
        $collection = new Collection([]);
        static::assertInstanceOf(RoundRobinStrategy::class, $collection->getSelectionStrategy());
    }

    public function testConstructorCanSetTheStrategy()
    {
        $collection = new Collection([], $this->strategy);
        static::assertSame($this->strategy, $collection->getSelectionStrategy());
    }

    public function testConstructorTakesListOfValidConnections()
    {
        $serverKey = 'my-unique-key';
        $connection = $this->getMockConnection($serverKey);
        $collection = new Collection([$connection]);
        static::assertSame($connection, $collection->getConnection($serverKey));
    }

    public function testConstructorChecksForValidConnections()
    {
        $this->expectException(InvalidArgumentException::class);

        new Collection(['sdfdsf']);
    }

    public function testForUnknownConnection()
    {
        $this->expectException(NotFoundException::class);

        $collection = new Collection([$this->getMockConnection('id-123')]);
        $collection->getConnection('foo-bar');
    }

    public function testCanSendToExactConnection()
    {
        $identifier = 'id-123';
        $command    = 'stats';
        $connection = $this->getMockConnection($identifier);
        $connection->expects(static::once())
            ->method($command);
        $collection = new Collection([
            $connection,
            $this->getMockConnection('id-456')
        ]);
        $collection->sendToExact($identifier, $command);
    }

    public function testSendToExactOnError()
    {
        $this->expectException(RuntimeException::class);

        $identifier = 'id-123';
        $command    = 'stats';
        $connection = $this->getMockConnection($identifier);
        $connection->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());
        $collection = new Collection([$connection]);
        $collection->sendToExact($identifier, $command);
    }

    public function testGetConnectionThatHasErrored()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Connection recently failed.');

        $identifier = 'id-123';
        $command    = 'stats';
        $connection = $this->getMockConnection($identifier);
        $connection->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());
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
        $connection->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());
        $collection = new Collection([$connection], $this->strategy, ['retry_delay' => 0]);
        try {
            $collection->sendToExact($identifier, $command);
        } catch (\Exception $e) {
            // void
        }
        static::assertSame($connection, $collection->getConnection($identifier));
    }

    public function testSendToAllConnections()
    {
        $command    = 'stats';
        $calls      = 0;
        $callback   = function () use (&$calls) {
            $calls++;
        };

        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects(static::any())
            ->method($command)
            ->willReturnCallback($callback);

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects(static::any())
            ->method($command)
            ->willReturnCallback($callback);

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command);
        static::assertEquals(2, $calls);
    }

    public function testSendToAllIgnoreErrors()
    {
        $command     = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects(static::once())
            ->method($command);

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command);
    }

    public function testSendToAllIgnoreErrorsOfNotFound()
    {
        $command     = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects(static::once())
            ->method($command);

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects(static::any())
            ->method($command)
            ->willThrowException(new NotFoundException());

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command);
    }

    public function testSendToAllCallsSuccessCallback()
    {
        $command     = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects(static::any())
            ->method($command);

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects(static::any())
            ->method($command);

        $called = 0;
        $onSuccess = function () use (&$called) {
            $called++;
        };

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command, [], $onSuccess);
        static::assertEquals(2, $called);
    }

    public function testSendToAllCallsFailureCallback()
    {
        $command     = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());

        $failed = 0;
        $onFailure = function () use (&$failed) {
            $failed++;
        };

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command, [], null, $onFailure);
        static::assertEquals(2, $failed);
    }

    public function testSendToOne()
    {
        $command     = 'stats';
        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects(static::once())
            ->method($command);

        $connection2 = $this->getMockConnection('id-456');

        $this->strategy->expects(static::any())
            ->method('pickOne')
            ->willReturn($identifier1);

        $collection = new Collection([$connection1, $connection2], $this->strategy);
        $collection->sendToOne($command);
    }

    public function testSendToOneWhenAllConnectionsAreUsed()
    {
        $this->expectException(RuntimeException::class);

        $command     = 'stats';
        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects(static::any())
            ->method($command)
            ->willReturn(false);

        $identifier2 = 'id-456';
        $connection2 = $this->getMockConnection($identifier2);
        $connection2->expects(static::any())
            ->method($command)
            ->willReturn(false);

        $this->strategy->expects(static::any())
            ->method('pickOne')
            ->willReturnOnConsecutiveCalls($identifier1, $identifier2);

        $collection = new Collection([$connection1, $connection2], $this->strategy);
        $collection->sendToOne($command);
    }

    public function testSendToOneIgnoresErrors()
    {
        $command     = 'stats';
        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());

        $identifier2 = 'id-456';
        $connection2 = $this->getMockConnection($identifier2);
        $connection2->expects(static::once())
            ->method($command)
            ->willReturn(234);

        $this->strategy->expects(static::any())
            ->method('pickOne')
            ->willReturnOnConsecutiveCalls($identifier1, $identifier2);

        $collection = new Collection([$connection1, $connection2], $this->strategy);
        $collection->sendToOne($command);
    }

    public function testSendToOneThrowsTheLastError()
    {
        $this->expectException(RuntimeException::class);

        $command     = 'stats';
        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());

        $identifier2 = 'id-456';
        $connection2 = $this->getMockConnection($identifier2);
        $connection2->expects(static::once())
            ->method($command)
            ->willThrowException(new RuntimeException());

        $this->strategy->expects(static::any())
            ->method('pickOne')
            ->willReturnOnConsecutiveCalls($identifier1, $identifier2);

        $collection = new Collection([$connection1, $connection2], $this->strategy);
        $collection->sendToOne($command);
    }

    /**
     * @param mixed $identifier
     * @param array $methods
     * @return Connection|MockObject
     */
    public function getMockConnection($identifier, array $methods = null)
    {
        $builder = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor();
        if ($methods) {
            $builder->setMethods($methods);
        }
        $connection = $builder->getMock();
        $connection->expects(static::any())
            ->method('getName')
            ->willReturn($identifier);
        return $connection;
    }
}
