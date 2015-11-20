<?php

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Pool;
use Phlib\Beanstalk\Pool\Collection;

class PoolTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $collection;

    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder('\Phlib\Beanstalk\Pool\Collection')
            ->disableOriginalConstructor()
            ->getMock();
        $this->pool = new Pool($this->collection);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->servers = null;
        $this->pool = null;
    }

    public function testUseTubeCallsAllConnections()
    {
        $tube = 'test-tube';
        $this->collection->expects($this->once())
            ->method('sendToAll');
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
        $connection = $this->getMockBuilder('\Phlib\Beanstalk\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection->expects($this->once())
            ->method('sendToOne')
            ->will($this->returnValue(['connection' => $connection, 'response' => '123']));
        $this->pool->put('myJobData');
    }

    public function testPutReturnsJobIdContainingTheServerIdentifier()
    {
        $host = 'host123';
        $connection = $this->createMockConnection($host);
        $this->collection->expects($this->any())
            ->method('sendToOne')
            ->will($this->returnValue(['connection' => $connection, 'response' => '123']));
        $this->assertContains($host, $this->pool->put('myJobData'));
    }

    public function testPutReturnsJobIdContainingTheOriginalJobId()
    {
        $jobId = '432';
        $connection = $this->createMockConnection('host:123');
        $this->collection->expects($this->any())
            ->method('sendToOne')
            ->will($this->returnValue(['connection' => $connection, 'response' => $jobId]));
        $this->assertContains($jobId, $this->pool->put('myJobData'));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\RuntimeException
     */
    public function testPutTotalFailure()
    {
        $this->collection->expects($this->any())
            ->method('sendToOne')
            ->will($this->throwException(new RuntimeException()));
        $this->pool->put('myJobData');
    }

    /**
     * @medium
     */
    public function testReserveWithNoJobsDoesNotTakeLongerThanTimeout()
    {
        $startTime = time();
        $this->pool->reserve(2);
        $totalTime = time() - $startTime;
        $this->assertGreaterThanOrEqual(2, $totalTime);
        $this->assertLessThanOrEqual(3, $totalTime);
    }

    public function testReserve()
    {
        $jobId      = '123';
        $host       = 'host:123';
        $response   = ['id' => $jobId, 'body' => 'jobData'];
        $expected   = ['id' => "host:123.$jobId", 'body' => 'jobData'];
        $connection = $this->createMockConnection($host);

        $this->collection->expects($this->any())
            ->method('sendToOne')
            ->will($this->returnValue(['connection' => $connection, 'response' => $response]));
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
        $host  = 'host:456';
        $jobId = '123';
        $this->collection->expects($this->once())
            ->method('sendToExact')
            ->with(
                $this->equalTo($host),
                $this->equalTo($method),
                $this->contains($jobId)
            );
        $this->pool->$method("$host.$jobId");
    }

    public function methodsWithJobIdDataProvider()
    {
        return [['delete'], ['release'], ['bury'], ['touch']];
    }

    public function testPeek()
    {
        $host       = 'host:456';
        $jobId      = '123';
        $jobBody    = 'jobBody';
        $response   = ['id' => $jobId, 'body' => $jobBody];
        $expected   = ['id' => "$host.$jobId", 'body' => $jobBody];
        $connection = $this->createMockConnection($host);

        $this->collection->expects($this->any())
            ->method('sendToExact')
            ->will($this->returnValue(['connection' => $connection, 'response' => $response]));

        $this->assertEquals($expected, $this->pool->peek("$host.$jobId"));
    }

    public function testPeekReady()
    {
        $host       = 'host:123';
        $jobId      = '123';
        $jobBody    = 'jobBody';
        $response   = ['id' => $jobId, 'body' => $jobBody];
        $expected   = ['id' => "$host.$jobId", 'body' => $jobBody];
        $connection = $this->createMockConnection($host);

        $this->collection->expects($this->any())
            ->method('sendToOne')
            ->will($this->returnValue(['connection' => $connection, 'response' => $response]));

        $this->assertEquals($expected, $this->pool->peekReady());
    }

    public function testPeekReadyWithNoReadyJobs()
    {
        $this->collection->expects($this->any())
            ->method('sendToOne')
            ->will($this->returnValue(['connection' => null, 'response' => false]));
        $this->assertFalse($this->pool->peekReady());
    }

    public function testPeekDelayed()
    {
        $host       = 'host:123';
        $jobId      = '123';
        $jobBody    = 'jobBody';
        $response   = ['id' => $jobId, 'body' => $jobBody];
        $expected   = ['id' => "$host.$jobId", 'body' => $jobBody];
        $connection = $this->createMockConnection($host);

        $this->collection->expects($this->any())
            ->method('sendToOne')
            ->will($this->returnValue(['connection' => $connection, 'response' => $response]));

        $this->assertEquals($expected, $this->pool->peekDelayed());
    }

    public function testPeekDelayedWithNoDelayedJobs()
    {
        $this->collection->expects($this->any())
            ->method('sendToOne')
            ->will($this->returnValue(['connection' => null, 'response' => false]));
        $this->assertFalse($this->pool->peekDelayed());
    }

    public function testPeekBuried()
    {
        $host       = 'host:123';
        $jobId      = '123';
        $jobBody    = 'jobBody';
        $response   = ['id' => $jobId, 'body' => $jobBody];
        $expected   = ['id' => "$host.$jobId", 'body' => $jobBody];
        $connection = $this->createMockConnection($host);

        $this->collection->expects($this->any())
            ->method('sendToOne')
            ->will($this->returnValue(['connection' => $connection, 'response' => $response]));

        $this->assertEquals($expected, $this->pool->peekBuried());
    }

    public function testPeekBuriedWithNoBuriedJobs()
    {
        $this->collection->expects($this->any())
            ->method('sendToOne')
            ->will($this->returnValue(['connection' => null, 'response' => false]));
        $this->assertFalse($this->pool->peekBuried());
    }

    public function testStats()
    {
        $noOfServers = 3;
        $ready       = 2;
        $other       = 8;
        $response    = ['current-jobs-ready' => $ready, 'some-other' => $other];
        $this->collection->expects($this->any())
            ->method('sendToAll')
            ->will($this->returnCallback(function ($command, $arguments, $success, $failure) use ($response, $noOfServers) {
                for ($i = 0; $i < $noOfServers; $i++) {
                    call_user_func($success, ['connection' => null, 'response' => $response]);
                }
            }));
        $this->assertEquals(
            ['current-jobs-ready' => ($ready * $noOfServers), 'some-other' => ($other * $noOfServers)],
            $this->pool->stats()
        );
    }

    public function testStatsJob()
    {
        $host       = 'host:123';
        $jobId      = '123';
        $hostJobId  = "$host.$jobId";
        $jobBody    = 'jobBody';
        $connection = $this->createMockConnection($host);
        $response   = ['id' => $jobId, 'body' => $jobBody];
        $expected   = ['id' => $hostJobId, 'body' => $jobBody];

        $this->collection->expects($this->any())
            ->method('sendToExact')
            ->will($this->returnValue(['connection' => $connection, 'response' => $response]));
        $this->assertEquals($expected, $this->pool->statsJob($hostJobId));
    }

    public function testStatsTube()
    {
        $noOfServers = 3;
        $ready       = 2;
        $other       = 8;
        $response    = ['current-jobs-ready' => $ready, 'some-other' => $other];
        $this->collection->expects($this->any())
            ->method('sendToAll')
            ->will($this->returnCallback(function ($command, $arguments, $success, $failure) use ($response, $noOfServers) {
                for ($i = 0; $i < $noOfServers; $i++) {
                    call_user_func($success, ['connection' => null, 'response' => $response]);
                }
            }));
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
        $connection = $this->createMockConnection('host:123');
        $connection->expects($this->any())
            ->method('kick')
            ->will($this->returnCallback(function ($quantity) {
                return ['response' => $quantity];
            }));

        $this->collection->expects($this->any())
            ->method('sendToAll')
            ->will($this->returnCallback(function ($command, $arguments, $success, $failure) use ($kickValues, $connection) {
                foreach ($kickValues as $count) {
                    $response = ['current-jobs-buried' => $count];
                    call_user_func($success, ['connection' => $connection, 'response' => $response]);
                }
            }));

        $this->assertEquals($expected, $this->pool->kick($kickAmount));
    }

    public function kickDataProvider()
    {
        return [
            [[1, 2, 4], 100, 7],
            [[1, 0, 4], 100, 5],
            [[0, 0, 0], 100, 0],
            [[33, 33], 100, 66],
            [[33, 33, 33], 100, 99],
            [[40, 40, 40], 100, 100],
        ];
    }

    public function testListTubes()
    {
        $expected = ['test1', 'test2', 'test3', 'test4'];

        $this->collection->expects($this->any())
            ->method('sendToAll')
            ->will($this->returnCallback(function ($command, $args, $success, $failure) use ($expected) {
                $success(['connection' => null, 'response' => array_slice($expected, 0, 2)]);
                $success(['connection' => null, 'response' => array_slice($expected, 2, 1)]);
                $success(['connection' => null, 'response' => array_slice($expected, 2, 2)]);
            }));

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
        list($actualHost, $actualJobId) = $this->pool->splitId($poolId);
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
