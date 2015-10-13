<?php

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\BeanstalkPool;

class BeanstalkPoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Phlib\Beanstalk\BeanstalkPool
     */
    protected $pool;

    /**
     * @var array
     */
    protected $config = array(
        'c1' => array(
            'host'   => '127.0.0.1',
            'port'   => 123,
            'weight' => 1
        ),
        'c2' => array(
            'host'   => '192.168.111.1',
            'port'   => 456,
            'weight' => 1
        ),
        'c3' => array(
            'host'   => '10.1.1.1',
            'port'   => 789
        ),
        'c4' => array(
            'host'   => '127.0.0.2',
            'port'   => 999,
            'weight' => 0
        ),
    );

    public function setUp()
    {
        parent::setUp();

        $configs = [
            'c1' => [
                'host' => '127.0.0.1',
                'port' => 123
            ],
            'c2' => [
                'host' => '192.168.111.1',
                'port' => 456
            ],
            'c3' => [
                'host' => '10.1.1.1',
                'port' => 789
            ],
            'c4' => [
                'host' => '127.0.0.2',
                'port' => 999
            ],
        ];
        $servers = [];
        foreach ($configs as $config) {
            $server = $this->getMockBuilder('\Phlib\Beanstalk\Beanstalk')
                ->disableOriginalConstructor()
                ->getMock();
            $server->expects($this->any())
                ->method('getUniqueIdentifier')
                ->willReturn("{$config['host']}:{$config['port']}");

            $servers[] = $server;
        }

        $this->pool = new BeanstalkPool($servers);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->pool = null;
    }

//    public function testGetBeanstalkFactory()
//    {
//        $pool = $this->pool;
//        $beanstalk = call_user_func($pool->getBeanstalkFactory(), $this->config['c1']);
//        $this->assertInstanceOf('\Mxm\Beanstalk\BeanstalkInterface', $beanstalk);
//    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception
     */
//    public function testInvalidBeanstalkFactory()
//    {
//        $pool = $this->pool;
//        $pool->setBeanstalkFactory(function() {
//            return new \stdClass();
//        });
//
//        $hash = $pool->getConnection('invalid');
//        $pool->getConnection($hash);
//    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception
     */
    public function testGetConnectionInvalidHash()
    {
        $this->pool->getConnection('InvalidHash');
    }

    public function testGetConnection()
    {
        $pool = $this->pool;
        $config = $this->config['c2'];

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $pool->setBeanstalkFactory(function($actualConfig) use ($mock, $config) {
            if ($actualConfig != $config) {
                throw new \Exception("Didn't expect that config");
            }
            return $mock;
        });

        $this->assertSame($mock, $pool->getConnection('c2'));
    }

    public function testGetCachedConnection()
    {
        $pool = $this->pool;
        $config = $this->config['c2'];

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $pool->setBeanstalkFactory(function() use ($mock) {
            static $called = false;
            if ($called) {
                throw new \Exception('Called more than once');
            }
            $called = true;

            return $mock;
        });

        $this->assertSame($mock, $pool->getConnection('c2'));
        $this->assertSame($mock, $pool->getConnection('c2'));
    }

//    public function testSplitId()
//    {
//        $expectedKey = 'c1';
//        $expectedId  = '123';
//        $id = "$expectedKey.$expectedId";
//
//        list($actualHash, $actualId) = $this->pool->splitId($id);
//
//        $this->assertEquals($expectedKey, $actualHash);
//        $this->assertEquals($expectedId, $actualId);
//    }

    public function testUseTubeNoConnections()
    {
        $pool = $this->pool;
        $pool->setBeanstalkFactory(function() {
            throw new \Exception('Unexpected call');
        });
        $this->assertSame('test', $pool->useTube('test'));
    }

    public function testUseTubeWith1Connection()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->once())
            ->method('useTube')
            ->with('test');

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $pool->getConnection('c1');

        $this->assertSame('test', $pool->useTube('test'));
    }

    public function testUseTubeWith2Connection()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->exactly(2))
            ->method('useTube')
            ->with('test');

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $pool->getConnection('c1');
        $pool->getConnection('c2');

        $this->assertSame('test', $pool->useTube('test'));
    }

    public function testUseWithConnectionMakeNewConnection()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->exactly(2))
            ->method('useTube')
            ->with('test');

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $pool->getConnection('c1');
        $this->assertSame('test', $pool->useTube('test'));
        $pool->getConnection('c2');
    }

    public function testIgnoreNoConnections()
    {
        $pool = $this->pool;
        $pool->setBeanstalkFactory(function() {
            throw new \Exception('Unexpected call');
        });
        $this->assertSame(1, $pool->ignore('test'));
    }

    public function testIgnoreLastIgnore()
    {
        $pool = $this->pool;
        $this->assertSame(false, $pool->ignore('default'));
    }

    public function testIgnore()
    {
        $pool = $this->pool;
        $pool->watch('test');
        $this->assertSame(1, $pool->ignore('default'));
    }

    public function testIgnoreConnected()
    {
        $pool = $this->pool;
        $pool->watch('test');

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->once())
            ->method('ignore')
            ->with('test');

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $pool->getConnection('c1');

        $this->assertSame(1, $pool->ignore('test'));
    }

    public function testIgnoreNewConnection()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->once())
            ->method('ignore')
            ->with('default');

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $pool->watch('test');
        $pool->ignore('default');

        $pool->getConnection('c1');
    }

//    public function testPutSuccess()
//    {
//        $pool = $this->pool;
//
//        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
//        $mock->expects($this->once())
//            ->method('put')
//            ->with(
//                'value123',
//                1024,
//                0,
//                60
//            )
//            ->will($this->returnValue(123));
//
//        $pool->setBeanstalkFactory(function() use ($mock) {
//            return $mock;
//        });
//
//        $id = $pool->put('value123');
//        list($key, $jobId) = $pool->splitId($id);
//
//        $this->assertTrue(array_search($key, array_keys($this->config)) !== false);
//        $this->assertSame(123, (int)$jobId);
//    }

//    public function testPutOneFailure()
//    {
//        $pool = $this->pool;
//
//        $mockFail = $this->getMock('\Mxm\Beanstalk\Connection');
//        $mockFail->expects($this->once())
//            ->method('put')
//            ->with(
//                'value123',
//                1024,
//                0,
//                60
//            )
//            ->will($this->throwException(new ConnectionException('Failed to write data')));
//
//        $mockSuccess = $this->getMock('\Mxm\Beanstalk\Connection');
//        $mockSuccess->expects($this->once())
//            ->method('put')
//            ->with(
//                'value123',
//                1024,
//                0,
//                60
//            )
//            ->will($this->returnValue(123));
//
//        $pool->setBeanstalkFactory(function() use ($mockFail, $mockSuccess) {
//            static $failed = false;
//
//            if (!$failed) {
//                $failed = true;
//                return $mockFail;
//            }
//
//            return $mockSuccess;
//        });
//
//        $id = $pool->put('value123');
//        list($key, $jobId) = $pool->splitId($id);
//
//        $this->assertTrue(array_search($key, array_keys($this->config)) !== false);
//        $this->assertSame(123, (int)$jobId);
//    }

    /**
     * @expectedException \Phlib\Beanstalk\ConnectionException
     */
    public function testPutTotalFailure()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->exactly(3))
            ->method('put')
            ->with(
                'value123',
                1024,
                0,
                60
            )
            ->will($this->throwException(new ConnectionException('Failed to write data')));

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $pool->put('value123');
    }

    /**
     * @large
     */
//    public function testPutRandomized()
//    {
//        $keyList = array_keys($this->config);
//
//        $pool = $this->pool;
//
//        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
//        $mock->expects($this->any())
//            ->method('put')
//            ->with(
//                'value123',
//                1024,
//                0,
//                60
//            )
//            ->will($this->returnValue(123));
//
//        $pool->setBeanstalkFactory(function() use ($mock) {
//            return $mock;
//        });
//
//        $tries = 100;
//        do {
//            $id = $pool->put('value123');
//            list($key, $jobId) = $pool->splitId($id);
//            $this->assertTrue(array_search($key, array_keys($this->config)) !== false);
//            $this->assertSame(123, (int)$jobId);
//
//            if (($idx = array_search($key, $keyList)) !== false) {
//                unset($keyList[$idx]);
//            }
//        } while($tries--);
//
//        // ensure we didn't exhaust our tries
//        $this->assertSame(1, count($keyList));
//    }

    /**
     * @large
     */
    public function testReserveNoJob()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->any())
            ->method('reserve')
            ->with(0)
            ->will($this->returnValue(false));

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $startTime = time();
        $this->assertSame(false, $pool->reserve(2));
        $endTime = time();
        $this->assertGreaterThanOrEqual(2, $endTime - $startTime);
        $this->assertLessThanOrEqual(3, $endTime - $startTime);
    }

//    public function testReserveJob()
//    {
//        $pool = $this->pool;
//
//        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
//        $mock->expects($this->once())
//            ->method('reserve')
//            ->with(0)
//            ->will(
//                $this->returnValue(
//                    array(
//                        'id'   => 123,
//                        'body' => 'value123'
//                    )
//                )
//            );
//
//        $pool->setBeanstalkFactory(function() use ($mock) {
//            return $mock;
//        });
//
//        $jobData = $pool->reserve();
//
//        $this->assertArrayHasKey('id', $jobData);
//        list($key, $jobId) = $pool->splitId($jobData['id']);
//        $this->assertTrue(array_search($key, array_keys($this->config)) !== false);
//        $this->assertSame(123, (int)$jobId);
//
//        $this->assertArrayHasKey('body', $jobData);
//        $this->assertSame('value123', $jobData['body']);
//    }

    /**
     * @large
     */
//    public function testReserveRandomized()
//    {
//        $keyList = array_keys($this->config);
//
//        $pool = $this->pool;
//
//        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
//        $mock->expects($this->any())
//            ->method('reserve')
//            ->with(0)
//            ->will(
//                $this->returnValue(
//                    array(
//                        'id'   => 123,
//                        'body' => 'value123'
//                    )
//                )
//            );
//
//        $pool->setBeanstalkFactory(function() use ($mock) {
//            return $mock;
//        });
//
//        $tries = 100;
//        do {
//            $jobData = $pool->reserve();
//
//            $this->assertArrayHasKey('id', $jobData);
//            list($key, $jobId) = $pool->splitId($jobData['id']);
//            $this->assertTrue(array_search($key, array_keys($this->config)) !== false);
//            $this->assertSame(123, (int)$jobId);
//
//            $this->assertArrayHasKey('body', $jobData);
//            $this->assertSame('value123', $jobData['body']);
//
//            if (($idx = array_search($key, $keyList)) !== false) {
//                unset($keyList[$idx]);
//            }
//        } while($tries--);
//
//        // ensure we didn't exhaust our tries
//        $this->assertSame(1, count($keyList));
//    }

    public function testDelete()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->once())
            ->method('delete')
            ->with(123)
            ->will($this->returnValue(true));

        $randKey = array_rand($this->config);
        $randConfig = $this->config[$randKey];

        $pool->setBeanstalkFactory(function($config) use ($mock, $randConfig) {
            if ($config != $randConfig) {
                throw new Exception("Didn't pick the correct connection");
            }

            return $mock;
        });

        $this->assertTrue($pool->delete("$randKey.123"));
    }

    public function testRelease()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->once())
            ->method('release')
            ->with(123, 1024, 0)
            ->will($this->returnValue(true));

        $randKey = array_rand($this->config);
        $randConfig = $this->config[$randKey];

        $pool->setBeanstalkFactory(function($config) use ($mock, $randConfig) {
            if ($config != $randConfig) {
                throw new Exception("Didn't pick the correct connection");
            }

            return $mock;
        });

        $this->assertTrue($pool->release("$randKey.123"));
    }

    public function testBury()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->once())
            ->method('bury')
            ->with(123, 1024)
            ->will($this->returnValue(true));

        $randKey = array_rand($this->config);
        $randConfig = $this->config[$randKey];

        $pool->setBeanstalkFactory(function($config) use ($mock, $randConfig) {
            if ($config != $randConfig) {
                throw new Exception("Didn't pick the correct connection");
            }

            return $mock;
        });

        $this->assertTrue($pool->bury("$randKey.123"));
    }

    public function testTouch()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->once())
            ->method('touch')
            ->with(123)
            ->will($this->returnValue(true));

        $randKey = array_rand($this->config);
        $randConfig = $this->config[$randKey];

        $pool->setBeanstalkFactory(function($config) use ($mock, $randConfig) {
            if ($config != $randConfig) {
                throw new Exception("Didn't pick the correct connection");
            }

            return $mock;
        });

        $this->assertTrue($pool->touch("$randKey.123"));
    }

    public function testPeek()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->once())
            ->method('peek')
            ->with(123)
            ->will(
                $this->returnValue(
                    array(
                        'id'   => 123,
                        'body' => 'value123'
                    )
                )
            );

        $randKey = array_rand($this->config);
        $randConfig = $this->config[$randKey];

        $pool->setBeanstalkFactory(function($config) use ($mock, $randConfig) {
            if ($config != $randConfig) {
                throw new Exception("Didn't pick the correct connection");
            }

            return $mock;
        });

        $this->assertTrue(is_array($pool->peek("$randKey.123")));
    }

//    public function testPeekReadyJob()
//    {
//        $pool = $this->pool;
//
//        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
//        $mock->expects($this->once())
//            ->method('peekReady')
//            ->will(
//                $this->returnValue(
//                    array(
//                        'id'   => 123,
//                        'body' => 'value123'
//                    )
//                )
//            );
//
//        $pool->setBeanstalkFactory(function() use ($mock) {
//            return $mock;
//        });
//
//        $jobData = $pool->peekReady();
//        $this->assertArrayHasKey('id', $jobData);
//        list($key, $jobId) = $pool->splitId($jobData['id']);
//        $this->assertTrue(array_search($key, array_keys($this->config)) !== false);
//        $this->assertSame(123, (int)$jobId);
//
//        $this->assertArrayHasKey('body', $jobData);
//        $this->assertSame('value123', $jobData['body']);
//    }

    public function testPeekReadyFalse()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->exactly(3))
            ->method('peekReady')
            ->will($this->returnValue(false));

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $this->assertFalse($pool->peekReady());
    }

    /**
     * @large
     */
//    public function testPeekReadyRandomized()
//    {
//        $keyList = array_keys($this->config);
//
//        $pool = $this->pool;
//
//        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
//        $mock->expects($this->any())
//            ->method('peekReady')
//            ->will(
//                $this->returnValue(
//                    array(
//                        'id'   => 123,
//                        'body' => 'value123'
//                    )
//                )
//            );
//
//        $pool->setBeanstalkFactory(function() use ($mock) {
//            return $mock;
//        });
//
//        $tries = 100;
//        do {
//            $jobData = $pool->peekReady();
//            list($key, $jobId) = $pool->splitId($jobData['id']);
//
//            if (($idx = array_search($key, $keyList)) !== false) {
//                unset($keyList[$idx]);
//            }
//        } while($tries--);
//
//        // ensure we didn't exhaust our tries
//        $this->assertSame(1, count($keyList));
//    }

    public function testPeekDelayed()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->exactly(3))
            ->method('peekDelayed')
            ->will($this->returnValue(false));

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $this->assertFalse($pool->peekDelayed());
    }

    public function testPeekBuried()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->exactly(3))
            ->method('peekBuried')
            ->will($this->returnValue(false));

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $this->assertFalse($pool->peekBuried());
    }

    public function testStats()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->exactly(3))
            ->method('stats')
            ->will(
                $this->returnValue(
                    array(
                        'current-jobs-ready' => 1,
                        'other-value'        => 1
                    )
                )
            );

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $stats = $pool->stats();
        $this->assertArrayHasKey('current-jobs-ready', $stats);
        $this->assertSame(3, $stats['current-jobs-ready']);
        $this->assertArrayNotHasKey('other-value', $stats);
    }

    public function testStatsJob()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->once())
            ->method('statsJob')
            ->with(123)
            ->will(
                $this->returnValue(
                    array(
                        'id'          => 123,
                        'other-stats' => 'value123'
                    )
                )
            );

        $randKey = array_rand($this->config);
        $randConfig = $this->config[$randKey];

        $pool->setBeanstalkFactory(function($config) use ($mock, $randConfig) {
            if ($config != $randConfig) {
                throw new Exception("Didn't pick the correct connection");
            }

            return $mock;
        });

        $id   = "$randKey.123";

        $statsJob = $pool->statsJob($id);
        $this->assertTrue(is_array($statsJob));
        $this->assertArrayHasKey('id', $statsJob);
        $this->assertSame($id, $statsJob['id']);
        $this->assertArrayHasKey('other-stats', $statsJob);
    }

    public function testStatsTube()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->exactly(3))
            ->method('statsTube')
            ->with('test')
            ->will(
                $this->returnValue(
                    array(
                        'current-jobs-ready' => 1,
                        'other-stats'        => 'value123'
                    )
                )
            );

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $stats = $pool->statsTube('test');
        $this->assertArrayHasKey('current-jobs-ready', $stats);
        $this->assertSame(3, $stats['current-jobs-ready']);
        $this->assertArrayNotHasKey('other-value', $stats);
    }

    public function testKick()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $mock->expects($this->exactly(3))
            ->method('statsTube')
            ->will(
                $this->onConsecutiveCalls(
                    array('current-jobs-buried' => 1),
                    array('current-jobs-buried' => 2),
                    array('current-jobs-buried' => 4)
                )
            );

        $mock->expects($this->exactly(3))
            ->method('kick')
            ->will($this->onConsecutiveCalls(1, 2, 4));

        $this->assertSame(7, $pool->kick(100));
    }

    public function testListTubes()
    {
        $pool = $this->pool;

        $mock = $this->getMock('\Mxm\Beanstalk\Connection');
        $mock->expects($this->exactly(3))
            ->method('listTubes')
            ->will(
                $this->onConsecutiveCalls(
                    array('test1', 'test2'),
                    array('test3'),
                    array('test1', 'test3')
                )
            );

        $pool->setBeanstalkFactory(function() use ($mock) {
            return $mock;
        });

        $tubes = $pool->listTubes();

        $this->assertTrue(is_array($tubes));
        $this->assertTrue(array_search('test1', $tubes) !== false);
        $this->assertTrue(array_search('test2', $tubes) !== false);
        $this->assertTrue(array_search('test3', $tubes) !== false);
        $this->assertTrue(array_search('test4', $tubes) === false);
    }

    public function testListTubeUsed()
    {
        $pool = $this->pool;
        $pool->useTube('test');
        $this->assertSame('test', $pool->listTubeUsed());
    }

    public function testListTubesWatched()
    {
        $pool = $this->pool;

        $watched = $pool->listTubesWatched();
        $this->assertTrue(is_array($watched));
        $this->assertSame(1, count($watched));
        $this->assertTrue(array_search('default', $watched) !== false);

        $pool->watch('test');
        $watched = $pool->listTubesWatched();
        $this->assertTrue(is_array($watched));
        $this->assertSame(2, count($watched));
        $this->assertTrue(array_search('default', $watched) !== false);
        $this->assertTrue(array_search('test', $watched) !== false);
    }
}
