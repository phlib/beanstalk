<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Exception\RuntimeException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\Stub;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    use PHPMock;

    private const SEND_COMMANDS_ALLOWED = [
        'useTube' => ['tube', 'void'],
        'put' => ['data', 123],
        'reserve' => [123, ['some result']],
        'touch' => [123, 'void'],
        'release' => [123, 'void'],
        'bury' => [123, 'void'],
        'delete' => [123, 'void'],
        'watch' => ['tube', 123],
        'ignore' => ['tube', 123],
        'peek' => [123, ['some result']],
        'statsJob' => [123, ['some result']],
        'peekReady' => [['some result']],
        'peekDelayed' => [['some result']],
        'peekBuried' => [['some result']],
        'kick' => [123, 123],
        'statsTube' => ['tube', ['some result']],
        'stats' => [['some result']],
        'listTubes' => [['some result']],
        'listTubeUsed' => ['some result'],
        'listTubesWatched' => [['some result']],
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Declare the namespaced function early, so it's available after being used in other tests
        // @see https://github.com/php-mock/php-mock-phpunit#restrictions
        PHPMock::defineFunctionMock(__NAMESPACE__, 'shuffle');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent the shuffle giving a random connection order
        $shuffle = $this->getFunctionMock(__NAMESPACE__, 'shuffle');
        $shuffle->expects(static::any())
            ->willReturn(null);
    }

    public function testImplementsCollectionInterface(): void
    {
        static::assertInstanceOf(CollectionInterface::class, new Collection([]));
    }

    public function testImplementsArrayAggregateInterface(): void
    {
        static::assertInstanceOf(\IteratorAggregate::class, new Collection([]));
    }

    public function testArrayAggregateReturnsConnections(): void
    {
        $result = true;
        $collection = new Collection([$this->getMockConnection('id-123'), $this->getMockConnection('id-456')]);
        foreach ($collection as $connection) {
            $result = $result && ($connection instanceof Connection);
        }
        static::assertTrue($result);
    }

    public function testArrayAggregateReturnsAllConnections(): void
    {
        $count = 0;
        $collection = new Collection([$this->getMockConnection('id-123'), $this->getMockConnection('id-456')]);
        foreach ($collection as $connection) {
            $count++;
        }
        static::assertSame(2, $count);
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
        static::assertSame(1, $count);
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

    public function testConstructorTakesListOfValidConnections(): void
    {
        $serverKey = 'my-unique-key';
        $connection = $this->getMockConnection($serverKey);
        $collection = new Collection([$connection]);
        static::assertSame($connection, $collection->getConnection($serverKey));
    }

    public function testConstructorChecksForValidConnections(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Collection(['sdfdsf']);
    }

    public function testForUnknownConnection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not in the pool');

        $collection = new Collection([$this->getMockConnection('id-123')]);
        $collection->getConnection('foo-bar');
    }

    public function testCanSendToExactConnection(): void
    {
        $identifier = 'id-123';
        $command = 'stats';
        $connection = $this->getMockConnection($identifier);
        $connection->expects(static::once())
            ->method($command);
        $collection = new Collection([
            $connection,
            $this->getMockConnection('id-456'),
        ]);
        $collection->sendToExact($identifier, $command);
    }

    public function testSendToExactOnError(): void
    {
        $this->expectException(RuntimeException::class);

        $identifier = 'id-123';
        $command = 'stats';
        $connection = $this->getMockConnection($identifier);
        $connection->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());
        $collection = new Collection([$connection]);
        $collection->sendToExact($identifier, $command);
    }

    /**
     * @dataProvider dataSendToExactCommandAllowed
     */
    public function testSendToExactCommandAllowed(string $command, array $placeholderArgs, ?Stub $returnStub): void
    {
        $identifier = 'id-123';
        $connection = $this->getMockConnection($identifier);
        $mockMethod = $connection->expects(static::once())
            ->method($command);
        if (isset($returnStub)) {
            $mockMethod->will($returnStub);
        }

        $collection = new Collection([$connection]);
        $collection->sendToExact($identifier, $command, $placeholderArgs);
    }

    public function dataSendToExactCommandAllowed(): iterable
    {
        /**
         * Allow calls to all command methods in
         * @see \Phlib\Beanstalk\ConnectionInterface
         */
        foreach (self::SEND_COMMANDS_ALLOWED as $command => $map) {
            $result = array_pop($map);
            if ($result === 'void') {
                $stub = null;
            } else {
                $stub = static::returnValue($result);
            }
            yield $command => [$command, $map, $stub];
        }
    }

    /**
     * @dataProvider dataSendToExactMethodNotAllowed
     */
    public function testSendToExactMethodNotAllowed(string $command): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Specified command '{$command}' is not allowed.");

        $identifier = 'id-123';
        $connection = $this->getMockConnection($identifier);
        $collection = new Collection([$connection]);

        // Expectation must be after the construction of Collection, as that calls `getName()`
        $connection->expects(static::never())
            ->method($command);

        $collection->sendToExact($identifier, $command);
    }

    public function dataSendToExactMethodNotAllowed(): iterable
    {
        /**
         * Block calls to non-command methods on
         * @see Connection
         */
        $connection = new \ReflectionClass(Connection::class);
        foreach ($connection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();
            if (array_key_exists($methodName, self::SEND_COMMANDS_ALLOWED)) {
                continue;
            }
            if ($methodName === '__construct') {
                continue;
            }
            yield $methodName => [$methodName];
        }
    }

    public function testGetConnectionThatHasErrored(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Connection recently failed.');

        $identifier = 'id-123';
        $command = 'stats';
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

    public function testGetConnectionThatHasErroredButIsDueRetry(): void
    {
        $identifier = 'id-123';
        $command = 'stats';
        $connection = $this->getMockConnection($identifier);
        $connection->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());
        $collection = new Collection([$connection], [
            'retry_delay' => 0,
        ]);
        try {
            $collection->sendToExact($identifier, $command);
        } catch (\Exception $e) {
            // void
        }
        static::assertSame($connection, $collection->getConnection($identifier));
    }

    public function testSendToAllConnections(): void
    {
        $command = 'stats';
        $calls = 0;
        $callback = function () use (&$calls) {
            $calls++;
            return [
                'current-jobs' => 123,
            ];
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
        static::assertSame(2, $calls);
    }

    public function testSendToAllIgnoreErrors(): void
    {
        $command = 'stats';
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

    public function testSendToAllIgnoreErrorsOfNotFound(): void
    {
        $command = 'stats';
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

    public function testSendToAllCallsSuccessCallback(): void
    {
        $command = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects(static::once())
            ->method($command);

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects(static::once())
            ->method($command);

        $called = 0;
        $onSuccess = function () use (&$called): bool {
            $called++;
            return true;
        };

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command, [], $onSuccess);
        static::assertSame(2, $called);
    }

    public function testSendToAllSuccessCallbackStopsIteration(): void
    {
        $command = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects(static::once())
            ->method($command);

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects(static::never())
            ->method($command);

        $called = 0;
        $onSuccess = function () use (&$called): bool {
            $called++;
            return false;
        };

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command, [], $onSuccess);
        static::assertSame(1, $called);
    }

    public function testSendToAllCallsFailureCallback(): void
    {
        $command = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects(static::any())
            ->method($command)
            ->willThrowException(new RuntimeException());

        $failed = 0;
        $onFailure = function () use (&$failed): bool {
            $failed++;
            return true;
        };

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command, [], null, $onFailure);
        static::assertSame(2, $failed);
    }

    public function testSendToAllFailureCallbackStopsIteration(): void
    {
        $command = 'stats';
        $connection1 = $this->getMockConnection('id-123');
        $connection1->expects(static::once())
            ->method($command)
            ->willThrowException(new RuntimeException());

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects(static::never())
            ->method($command)
            ->willThrowException(new RuntimeException());

        $failed = 0;
        $onFailure = function () use (&$failed): bool {
            $failed++;
            return false;
        };

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToAll($command, [], null, $onFailure);
        static::assertSame(1, $failed);
    }

    public function testSendToOne(): void
    {
        $command = 'peekReady';
        $response = [
            'id' => 123,
            'body' => 'jobData',
        ];

        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects(static::once())
            ->method($command)
            ->willReturn($response);

        $connection2 = $this->getMockConnection('id-456');
        $connection2->expects(static::never())
            ->method($command);

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToOne($command);
    }

    public function testSendToOneIgnoresNotFound(): void
    {
        $command = 'peekReady';
        $response = [
            'id' => 123,
            'body' => 'jobData',
        ];

        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects(static::once())
            ->method($command)
            ->willThrowException(new NotFoundException(
                NotFoundException::PEEK_STATUS_MSG,
                NotFoundException::PEEK_STATUS_CODE,
            ));

        $identifier2 = 'id-456';
        $connection2 = $this->getMockConnection($identifier2);
        $connection2->expects(static::once())
            ->method($command)
            ->willReturn($response);

        $collection = new Collection([$connection1, $connection2]);
        $actual = $collection->sendToOne($command);

        static::assertSame($response, $actual['response']);
    }

    public function testSendToOneThrowsTheLastNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('error2');
        $this->expectExceptionCode(NotFoundException::PEEK_STATUS_CODE);

        $command = 'peekReady';
        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects(static::once())
            ->method($command)
            ->willThrowException(new NotFoundException(
                // Deliberate non-standard message to track which exception is returned
                'error1',
                NotFoundException::PEEK_STATUS_CODE,
            ));

        $identifier2 = 'id-456';
        $connection2 = $this->getMockConnection($identifier2);
        $connection2->expects(static::once())
            ->method($command)
            ->willThrowException(new NotFoundException(
                // Deliberate non-standard message to track which exception is returned
                'error2',
                NotFoundException::PEEK_STATUS_CODE,
            ));

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToOne($command);
    }

    public function testSendToOneIgnoresErrors(): void
    {
        $command = 'peekReady';
        $response = [
            'id' => 123,
            'body' => 'jobData',
        ];

        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects(static::once())
            ->method($command)
            ->willThrowException(new RuntimeException());

        $identifier2 = 'id-456';
        $connection2 = $this->getMockConnection($identifier2);
        $connection2->expects(static::once())
            ->method($command)
            ->willReturn($response);

        $collection = new Collection([$connection1, $connection2]);
        $collection->sendToOne($command);
    }

    public function testSendToOneThrowsTheLastError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to send command');

        $command = 'peekReady';
        $identifier1 = 'id-123';
        $connection1 = $this->getMockConnection($identifier1);
        $connection1->expects(static::once())
            ->method($command)
            ->willThrowException(new RuntimeException('error1'));

        $identifier2 = 'id-456';
        $connection2 = $this->getMockConnection($identifier2);
        $connection2->expects(static::once())
            ->method($command)
            ->willThrowException(new RuntimeException('error2'));

        try {
            $collection = new Collection([$connection1, $connection2]);
            $collection->sendToOne($command);
        } catch (RuntimeException $e) {
            $previous = $e->getPrevious();
            static::assertInstanceOf(RuntimeException::class, $previous);
            static::assertSame('error2', $previous->getMessage());
            throw $e;
        }
    }

    public function testSendToOneThrowsExceptionWhenNoAvailableConnections(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to send command');

        $command = 'peekReady';

        try {
            $collection = new Collection([]);
            $collection->sendToOne($command);
        } catch (RuntimeException $e) {
            $previous = $e->getPrevious();
            static::assertNull($previous);
            throw $e;
        }
    }

    /**
     * @return Connection|MockObject
     */
    private function getMockConnection(string $identifier): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getName')
            ->willReturn($identifier);
        return $connection;
    }
}
