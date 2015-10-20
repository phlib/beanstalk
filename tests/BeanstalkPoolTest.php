<?php

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Beanstalk;
use Phlib\Beanstalk\BeanstalkPool;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Exception\SocketException;
use phpmock\phpunit\PHPMock;

class BeanstalkPoolTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    /**
     * @var BeanstalkPool
     */
    protected $pool;

    /**
     * @var Beanstalk[]
     */
    protected $servers;

    public function setUp()
    {
        parent::setUp();

        $builder = $this->getMockBuilder('\Phlib\Beanstalk\Beanstalk')
            ->disableOriginalConstructor();

        $conn1 = $builder->getMock();
        $conn1->expects($this->any())
            ->method('getUniqueIdentifier')
            ->willReturn('host:123');

        $conn2 = $builder->getMock();
        $conn2->expects($this->any())
            ->method('getUniqueIdentifier')
            ->willReturn('host:456');

        $conn3 = $builder->getMock();
        $conn3->expects($this->any())
            ->method('getUniqueIdentifier')
            ->willReturn('host:789');

        $this->servers = [$conn1, $conn2, $conn3];
        $this->pool = new BeanstalkPool($this->servers);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->servers = null;
        $this->pool = null;
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testGetConnectionWithInvalidHash()
    {
        $this->pool->getConnection('InvalidHash');
    }

    public function testGetConnection()
    {
        $beanstalk = $this->pool->getConnection('host:789');
        $this->assertSame($this->servers[2], $beanstalk);
    }

    public function testUseTubeCallsEachConnection()
    {
        $tube = 'test-tube';
        foreach (array_keys($this->servers) as $index) {
            $this->servers[$index]->expects($this->once())
                ->method('useTube')
                ->with($tube)
                ->willReturn($tube);
        }
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
        $jobData = 'myJobData';
        $randomConnection = $this->servers[0];

        $this->setupExpectedShuffle();
        $randomConnection->expects($this->once())
            ->method('put')
            ->with($jobData)
            ->willReturn('abc123');

        $this->pool->put($jobData);
    }

    public function testPutReturnsJobIdThatStartsWithServerIdentifier()
    {
        $randomConnection = $this->servers[0];

        $this->setupExpectedShuffle();
        $serverJobId = 'abc123';
        $randomConnection->expects($this->once())
            ->method('put')
            ->willReturn($serverJobId);

        $expectedJobId = $randomConnection->getUniqueIdentifier() . '.' . $serverJobId;
        $this->assertEquals($expectedJobId, $this->pool->put('myJobData'));
    }

    public function testPutFailureFailsToAnotherConnection()
    {
        $this->setupExpectedShuffle();
        $this->servers[0]->expects($this->once())
            ->method('put')
            ->will($this->throwException(new SocketException('Failed to write data.')));
        $this->servers[1]->expects($this->once())
            ->method('put')
            ->willReturn('abc123');

        $this->pool->put('myJobData');
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\SocketException
     */
    public function testPutTotalFailure()
    {
        $this->servers[0]->expects($this->once())
            ->method('put')
            ->will($this->throwException(new SocketException('Failed to write data.')));
        $this->servers[1]->expects($this->once())
            ->method('put')
            ->will($this->throwException(new SocketException('Failed to write data.')));
        $this->servers[2]->expects($this->once())
            ->method('put')
            ->will($this->throwException(new SocketException('Failed to write data.')));

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
        $jobId = 'abc123';
        $hostJobId = "host:123.$jobId";
        $this->setupExpectedShuffle();
        $this->servers[0]->expects($this->any())
            ->method('reserve')
            ->willReturn(['id' => $jobId, 'body' => 'jobData']);
        $this->assertEquals(['id' => $hostJobId, 'body' => 'jobData'], $this->pool->reserve());
    }

    public function testDelete()
    {
        $jobId = 'abc123';
        $this->servers[1]->expects($this->once())
            ->method('delete')
            ->with($jobId)
            ->will($this->returnSelf());
        // returns self
        $this->assertSame($this->pool, $this->pool->delete("host:456.$jobId"));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testPoolIdWithInvalidFormat()
    {
        $this->pool->release('abc123');
    }

    public function testRelease()
    {
        $jobId = 'abc123';
        $this->servers[1]->expects($this->any())
            ->method('release')
            ->with($jobId)
            ->will($this->returnSelf());
        // returns self
        $this->assertSame($this->pool, $this->pool->release("host:456.$jobId"));
    }

    public function testBury()
    {
        $jobId = 'abc123';
        $this->servers[1]->expects($this->any())
            ->method('bury')
            ->with($jobId)
            ->will($this->returnSelf());
        // returns self
        $this->assertSame($this->pool, $this->pool->bury("host:456.$jobId"));
    }

    public function testTouch()
    {
        $jobId = 'abc123';
        $this->servers[1]->expects($this->any())
            ->method('touch')
            ->with($jobId)
            ->will($this->returnSelf());
        // returns self
        $this->assertSame($this->pool, $this->pool->touch("host:456.$jobId"));
    }

    public function testPeek()
    {
        $jobId     = 'abc123';
        $hostJobId = "host:456.$jobId";
        $this->servers[1]->expects($this->any())
            ->method('peek')
            ->with($jobId)
            ->willReturn(['id' => $jobId, 'body' => 'jobBody']);
        // returns self
        $this->assertEquals(['id' => $hostJobId, 'body' => 'jobBody'], $this->pool->peek("host:456.$jobId"));
    }

    public function testPeekReady()
    {
        $this->setupExpectedShuffle();
        $jobId     = 'abc123';
        $hostJobId = "host:123.$jobId";
        $this->servers[0]->expects($this->any())
            ->method('peekReady')
            ->willReturn(['id' => $jobId, 'body' => 'jobBody']);
        $this->assertEquals(['id' => $hostJobId, 'body' => 'jobBody'], $this->pool->peekReady());
    }

    public function testPeekReadyWithNoReadyJobs()
    {
        $this->servers[0]->expects($this->any())
            ->method('peekReady')
            ->willReturn(false);
        $this->servers[1]->expects($this->any())
            ->method('peekReady')
            ->willReturn(false);
        $this->servers[2]->expects($this->any())
            ->method('peekReady')
            ->willReturn(false);
        $this->assertFalse($this->pool->peekReady());
    }

    public function testPeekDelayed()
    {
        $this->setupExpectedShuffle();
        $jobId     = 'abc123';
        $hostJobId = "host:123.$jobId";
        $this->servers[0]->expects($this->any())
            ->method('peekDelayed')
            ->willReturn(['id' => $jobId, 'body' => 'jobBody']);
        $this->assertEquals(['id' => $hostJobId, 'body' => 'jobBody'], $this->pool->peekDelayed());
    }

    public function testPeekDelayedWithNoDelayedJobs()
    {
        $this->servers[0]->expects($this->any())
            ->method('peekDelayed')
            ->willReturn(false);
        $this->servers[1]->expects($this->any())
            ->method('peekDelayed')
            ->willReturn(false);
        $this->servers[2]->expects($this->any())
            ->method('peekDelayed')
            ->willReturn(false);
        $this->assertFalse($this->pool->peekDelayed());
    }

    public function testPeekBuried()
    {
        $this->setupExpectedShuffle();
        $jobId     = 'abc123';
        $hostJobId = "host:123.$jobId";
        $this->servers[0]->expects($this->any())
            ->method('peekBuried')
            ->willReturn(['id' => $jobId, 'body' => 'jobBody']);
        $this->assertEquals(['id' => $hostJobId, 'body' => 'jobBody'], $this->pool->peekBuried());
    }

    public function testPeekBuriedWithNoBuriedJobs()
    {
        $this->servers[0]->expects($this->any())
            ->method('peekBuried')
            ->willReturn(false);
        $this->servers[1]->expects($this->any())
            ->method('peekBuried')
            ->willReturn(false);
        $this->servers[2]->expects($this->any())
            ->method('peekBuried')
            ->willReturn(false);
        $this->assertFalse($this->pool->peekBuried());
    }

    public function testStats()
    {
        $expected = ['current-jobs-ready' => 6];
        foreach (array_keys($this->servers) as $index) {
            $this->servers[$index]->expects($this->any())
                ->method('stats')
                ->willReturn(['current-jobs-ready' => 2, 'some-other' => 8]);
        }
        $this->assertEquals($expected, $this->pool->stats());
    }

    public function testStatsWithOnInvalidResponse()
    {
        $expected = ['current-jobs-ready' => 4];
        foreach (array_keys($this->servers) as $index) {
            if ($index == 1) {
                continue;
            }
            $this->servers[$index]->expects($this->any())
                ->method('stats')
                ->willReturn(['current-jobs-ready' => 2, 'some-other' => 8]);
        }
        $this->assertEquals($expected, $this->pool->stats());
    }

    public function testStatsJob()
    {
        $this->setupExpectedShuffle();
        $jobId     = 'abc123';
        $hostJobId = "host:123.$jobId";
        $this->servers[0]->expects($this->any())
            ->method('statsJob')
            ->willReturn(['id' => $jobId, 'some-other' => 8]);
        $this->assertEquals(['id' => $hostJobId, 'some-other' => 8], $this->pool->statsJob($hostJobId));
    }

    public function testStatsTube()
    {
        $expected = ['current-jobs-ready' => 6];
        foreach (array_keys($this->servers) as $index) {
            $this->servers[$index]->expects($this->any())
                ->method('statsTube')
                ->willReturn(['current-jobs-ready' => 2, 'some-other' => 8]);
        }
        $this->assertEquals($expected, $this->pool->statsTube('test-tube'));
    }

    public function testStatsTubeWithOnInvalidResponse()
    {
        $expected = ['current-jobs-ready' => 4];
        foreach (array_keys($this->servers) as $index) {
            if ($index == 1) {
                continue;
            }
            $this->servers[$index]->expects($this->any())
                ->method('statsTube')
                ->willReturn(['current-jobs-ready' => 2, 'some-other' => 8]);
        }
        $this->assertEquals($expected, $this->pool->statsTube('test-tube'));
    }

    public function testKick()
    {
        $expected = [1, 2, 4];
        foreach ($expected as $index => $count) {
            $this->servers[$index]->expects($this->any())
                ->method('statsTube')
                ->willReturn(['current-jobs-buried' => $count]);
            $this->servers[$index]->expects($this->any())
                ->method('kick')
                ->willReturn($count);
        }
        $this->assertSame(array_sum($expected), $this->pool->kick(100));
    }

    public function testKickWithinBounds()
    {
        $this->setupExpectedShuffle();
        $expected = 5;
        foreach ([1, 2, 4] as $index => $count) {
            $this->servers[$index]->expects($this->any())
                ->method('statsTube')
                ->willReturn(['current-jobs-buried' => $count]);
            $this->servers[$index]->expects($this->any())
                ->method('kick')
                ->will($this->returnArgument(0));
        }
        $this->assertSame($expected, $this->pool->kick($expected));
    }

    public function testListTubes()
    {
        $expected = ['test1', 'test2', 'test3', 'test4'];
        $this->servers[0]->expects($this->any())
            ->method('listTubes')
            ->willReturn(array_slice($expected, 0, 2));
        $this->servers[1]->expects($this->any())
            ->method('listTubes')
            ->willReturn(array_slice($expected, 2, 1));
        $this->servers[2]->expects($this->any())
            ->method('listTubes')
            ->willReturn(array_slice($expected, 2, 2));

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

    protected function setupExpectedShuffle()
    {
        $shuffle = $this->getFunctionMock('\Phlib\Beanstalk', 'shuffle');
        $shuffle->expects($this->any())
            ->willReturnCallback(function (&$keys) {
                $keys = ['host:123', 'host:456', 'host:789'];
            });
    }
}
