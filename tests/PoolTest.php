<?php

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Pool;
use phpmock\phpunit\PHPMock;

class PoolTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection1;

    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection2;

    public function setUp()
    {
        parent::setUp();

        // stop the shuffle giving us random results
        $shuffle = $this->getFunctionMock('\Phlib\Beanstalk', 'shuffle');
        $shuffle->expects($this->any())->willReturn(null);

        $this->connection1 = $this->createMock(Connection::class);
        $this->connection1->method('getName')->willReturn('connection1');
        $this->connection2 = $this->createMock(Connection::class);
        $this->connection2->method('getName')->willReturn('connection2');

        $this->pool = new Pool([$this->connection1, $this->connection2]);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->pool = null;
        $this->connection1 = null;
        $this->connection2 = null;
    }

    public function testDisconnectCallsAllConnections()
    {
        $this->connection1->expects($this->once())
            ->method('disconnect')
            ->willReturn(true);
        $this->connection2->expects($this->once())
            ->method('disconnect')
            ->willReturn(true);
        $this->pool->disconnect();
    }

    /**
     * @param bool $expected
     * @param array $returnValues
     * @dataProvider disconnectReturnsValueDataProvider
     */
    public function testDisconnectReturnsValue($expected, array $returnValues)
    {
        $this->connection1->expects($this->any())
            ->method('disconnect')
            ->willReturn($returnValues[0]);
        $this->connection2->expects($this->any())
            ->method('disconnect')
            ->willReturn($returnValues[1]);
        $this->assertEquals($expected, $this->pool->disconnect());
    }

    public function disconnectReturnsValueDataProvider()
    {
        return [
            [true, [true, true]],
            [false, [false, true]],
            [false, [true, false]],
            [false, [false, false]],
        ];
    }

    public function testUseTubeCallsAllConnections()
    {
        $tube = 'test-tube';
        $this->connection1->expects($this->once())
            ->method('useTube');
        $this->connection2->expects($this->once())
            ->method('useTube');
        $this->pool->useTube($tube);
    }

    public function testIgnoreDoesNotAllowLessThanOneWatching()
    {
        // 'default' tube is already being watched
        $this->assertFalse($this->pool->ignore('default'));
    }

    public function testIgnore()
    {
        $this->pool->watch('test-tube');
        $this->assertEquals(1, $this->pool->ignore('default'));
    }

    public function testPutSuccess()
    {
        $this->connection1->expects($this->once())
            ->method('put')
            ->willReturn(234);
        $this->pool->put('myJobData');
    }

    public function testPutReturnsJobIdContainingTheServerIdentifier()
    {
        $this->connection1->method('put')->willReturn(234);
        $this->assertContains($this->connection1->getName(), $this->pool->put('myJobData'));
    }

    public function testPutReturnsJobIdContainingTheOriginalJobId()
    {
        $jobId = '432';
        $this->connection1->method('put')->willReturn($jobId);
        $this->assertContains($jobId, $this->pool->put('myJobData'));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\RuntimeException
     */
    public function testPutTotalFailure()
    {
        $this->connection1->method('put')->will($this->throwException(new RuntimeException()));
        $this->connection2->method('put')->will($this->throwException(new RuntimeException()));
        $this->pool->put('myJobData');
    }

    /**
     * @medium
     */
    public function testReserveWithNoJobsDoesNotTakeLongerThanTimeout()
    {
        $this->connection1->method('reserve')->willReturn(false);
        $this->connection2->method('reserve')->willReturn(false);
        $startTime = time();
        $this->pool->reserve(2);
        $totalTime = time() - $startTime;
        $this->assertGreaterThanOrEqual(2, $totalTime);
        $this->assertLessThanOrEqual(3, $totalTime);
    }

    public function testReserve()
    {
        $jobId    = '123';
        $host     = $this->connection1->getName();
        $response = ['id' => $jobId, 'body' => 'jobData'];
        $expected = ['id' => "{$host}.{$jobId}", 'body' => 'jobData'];

        $this->connection1->method('reserve')->willReturn($response);
        $this->assertEquals($expected, $this->pool->reserve());
    }

    public function testReserveWithNoJobsOnFirstServer()
    {
        $jobId    = '123';
        $host     = $this->connection2->getName();
        $response = ['id' => $jobId, 'body' => 'jobData'];
        $expected = ['id' => "{$host}.{$jobId}", 'body' => 'jobData'];

        $this->connection1->method('reserve')->willReturn(false);
        $this->connection2->method('reserve')->willReturn($response);
        $this->assertEquals($expected, $this->pool->reserve());
    }

    public function testReserveWithFailingServer()
    {
        $jobId    = '123';
        $host     = $this->connection2->getName();
        $response = ['id' => $jobId, 'body' => 'jobData'];
        $expected = ['id' => "{$host}.{$jobId}", 'body' => 'jobData'];

        $this->connection1->method('reserve')->willThrowException(new RuntimeException());
        $this->connection2->method('reserve')->willReturn($response);
        $this->assertEquals($expected, $this->pool->reserve());
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testPoolIdWithInvalidFormat()
    {
        $this->pool->release('123');
    }

    /**
     * @param string $method
     * @dataProvider methodsWithJobIdDataProvider
     */
    public function testMethodsWithJobId($method)
    {
        $host  = $this->connection1->getName();
        $jobId = 123;

        $this->connection1->expects($this->once())
            ->method($method)
            ->with($this->equalTo($jobId));
        $this->pool->$method("$host.$jobId");
    }

    public function methodsWithJobIdDataProvider()
    {
        return [['delete'], ['release'], ['bury'], ['touch']];
    }

    public function testPeek()
    {
        $host     = $this->connection2->getName();
        $jobId    = '123';
        $jobBody  = 'jobBody';
        $response = ['id' => $jobId, 'body' => $jobBody];
        $expected = ['id' => "$host.$jobId", 'body' => $jobBody];

        $this->connection2->method('peek')->willReturn($response);
        $this->assertEquals($expected, $this->pool->peek("$host.$jobId"));
    }

    public function testPeekReady()
    {
        $host     = $this->connection2->getName();
        $jobId    = '123';
        $jobBody  = 'jobBody';
        $response = ['id' => $jobId, 'body' => $jobBody];
        $expected = ['id' => "$host.$jobId", 'body' => $jobBody];

        $this->connection1->method('peekReady')->willReturn(false);
        $this->connection2->method('peekReady')->willReturn($response);
        $this->assertEquals($expected, $this->pool->peekReady());
    }

    public function testPeekReadyWithNoReadyJobs()
    {
        $this->connection1->method('peekReady')->willReturn(false);
        $this->connection2->method('peekReady')->willReturn(false);
        $this->assertFalse($this->pool->peekReady());
    }

    public function testPeekDelayed()
    {
        $host     = $this->connection2->getName();
        $jobId    = '123';
        $jobBody  = 'jobBody';
        $response = ['id' => $jobId, 'body' => $jobBody];
        $expected = ['id' => "$host.$jobId", 'body' => $jobBody];

        $this->connection1->method('peekDelayed')->willReturn(false);
        $this->connection2->method('peekDelayed')->willReturn($response);
        $this->assertEquals($expected, $this->pool->peekDelayed());
    }

    public function testPeekDelayedWithNoDelayedJobs()
    {
        $this->connection1->method('peekDelayed')->willReturn(false);
        $this->connection2->method('peekDelayed')->willReturn(false);
        $this->assertFalse($this->pool->peekDelayed());
    }

    public function testPeekBuried()
    {
        $host     = $this->connection2->getName();
        $jobId    = '123';
        $jobBody  = 'jobBody';
        $response = ['id' => $jobId, 'body' => $jobBody];
        $expected = ['id' => "$host.$jobId", 'body' => $jobBody];

        $this->connection1->method('peekBuried')->willReturn(false);
        $this->connection2->method('peekBuried')->willReturn($response);
        $this->assertEquals($expected, $this->pool->peekBuried());
    }

    public function testPeekBuriedWithNoBuriedJobs()
    {
        $this->connection1->method('peekBuried')->willReturn(false);
        $this->connection2->method('peekBuried')->willReturn(false);
        $this->assertFalse($this->pool->peekBuried());
    }

    public function testStats()
    {
        $noOfServers = 2;
        $ready       = 2;
        $other       = 8;
        $response    = ['current-jobs-ready' => $ready, 'some-other' => $other];
        $this->connection1->method('stats')->willReturn($response);
        $this->connection2->method('stats')->willReturn($response);
        $this->assertEquals(
            ['current-jobs-ready' => ($ready * $noOfServers), 'some-other' => ($other * $noOfServers)],
            $this->pool->stats()
        );
    }

    public function testStatsJob()
    {
        $host      = $this->connection2->getName();
        $jobId     = '123';
        $hostJobId = "$host.$jobId";
        $jobBody   = 'jobBody';
        $response  = ['id' => $jobId, 'body' => $jobBody];
        $expected  = ['id' => $hostJobId, 'body' => $jobBody];

        $this->connection2->method('statsJob')->willReturn($response);
        $this->assertEquals($expected, $this->pool->statsJob($hostJobId));
    }

    public function testStatsTube()
    {
        $noOfServers = 2;
        $ready       = 2;
        $other       = 8;
        $response    = ['current-jobs-ready' => $ready, 'some-other' => $other];
        $this->connection1->method('statsTube')->willReturn($response);
        $this->connection2->method('statsTube')->willReturn($response);
        $this->assertEquals(
            ['current-jobs-ready' => ($ready * $noOfServers), 'some-other' => ($other * $noOfServers)],
            $this->pool->statsTube('test-tube')
        );
    }

    /**
     * @param array $kickValues
     * @param integer $kickAmount
     * @param integer $expected
     * @dataProvider kickDataProvider
     */
    public function testKick(array $kickValues, $kickAmount, $expected)
    {
        foreach ($kickValues as $index => $kickValue) {
            $connection = 'connection' . ($index + 1);
            $this->{$connection}->method('statsTube')->willReturn(['current-jobs-buried' => $kickValue]);
            $this->{$connection}->method('kick')->will($this->returnCallback(function ($quantity) use ($kickValue) {
                return $quantity < $kickValue ? $quantity : $kickValue;
            }));
        }

        $this->assertEquals($expected, $this->pool->kick($kickAmount));
    }

    public function kickDataProvider()
    {
        return [
            [[1, 5], 100, 6],
            [[1, 4], 100, 5],
            [[0, 0], 100, 0],
            [[33, 33], 100, 66],
            [[33, 66], 100, 99],
            [[40, 80], 100, 100],
        ];
    }

    public function testListTubes()
    {
        $expected = ['test1', 'test2', 'test3', 'test4'];

        $this->connection1->method('listTubes')->willReturn(array_slice($expected, 0, 2));
        $this->connection2->method('listTubes')->willReturn(array_slice($expected, 1));

        $actual = $this->pool->listTubes();
        sort($actual); // this is so they match
        $this->assertEquals($expected, $actual);
    }

    public function testListTubeUsed()
    {
        $tube = 'test-tube';
        $this->pool->useTube($tube);
        $this->assertSame($tube, $this->pool->listTubeUsed());
    }

    public function testListTubesWatchDefaultState()
    {
        $this->assertEquals(['default'], $this->pool->listTubesWatched());
    }

    public function testListTubesWatched()
    {
        $this->pool->watch('test');
        $this->assertEquals(['default', 'test'], $this->pool->listTubesWatched());
    }

    public function testCombineIdIsNotTheJobId()
    {
        $jobId = 123;
        $connection = $this->createMockConnection('host');
        $this->assertNotEquals($jobId, $this->pool->combineId($connection, $jobId));
    }

    public function testCombineIdContainsJob()
    {
        $jobId = 123;
        $connection = $this->createMockConnection('host');
        $this->assertContains((string)$jobId, $this->pool->combineId($connection, $jobId));
    }

    public function testCombineAndSplitReturnCorrectJob()
    {
        $jobId = 234;
        $connection = $this->createMockConnection('127.0.0.1');

        $poolId = $this->pool->combineId($connection, $jobId);
        list(, $actualJobId) = $this->pool->splitId($poolId);
        $this->assertEquals($jobId, $actualJobId);
    }

    public function testCombineAndSplitReturnCorrectHost()
    {
        $host = '127.0.0.1';
        $connection = $this->createMockConnection($host);

        $poolId = $this->pool->combineId($connection, 123);
        list($actualHost, ) = $this->pool->splitId($poolId);
        $this->assertEquals($host, $actualHost);
    }

    /**
     * @param string $host
     * @return Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockConnection($host)
    {
        $connection = $this->getMockBuilder('\Phlib\Beanstalk\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($host));
        return $connection;
    }
}
