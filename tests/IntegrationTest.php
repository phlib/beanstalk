<?php

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Connection\Socket;

/**
 * @runTestsInSeparateProcesses
 * @group integration
 */
class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    protected $beanstalk;

    public function setUp()
    {
        if (!isset($GLOBALS['BSTALK_ENABLED']) || $GLOBALS['BSTALK_ENABLED'] != true) {
            $this->markTestSkipped();
        } else {
            $this->beanstalk = new Connection(new Socket($GLOBALS['BSTALK1_HOST'], $GLOBALS['BSTALK1_PORT']));
        }
    }

    public function testReconnectingAfterDisconnect()
    {
        $this->beanstalk->listTubes(); // make sure we connect
        $this->beanstalk->disconnect();

        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        $this->assertContains($tube, $this->beanstalk->listTubes());
    }

    public function testStartWithDefaultTube()
    {
        $this->assertEquals('default', $this->beanstalk->listTubeUsed());
    }

    public function testSwitchingUsedTube()
    {
        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        $this->assertEquals($tube, $this->beanstalk->listTubeUsed());
    }

    public function testStartWithDefaultWatching()
    {
        $this->assertEquals(['default'], $this->beanstalk->listTubesWatched());
    }

    public function testWatchingMoreTubes()
    {
        $tube = 'test-tube';
        $this->beanstalk->watch($tube);
        $this->assertContains($tube, $this->beanstalk->listTubesWatched());
    }

    public function testListTubes()
    {
        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        $this->assertContains($tube, $this->beanstalk->listTubes());
    }

    public function testFullJobProcess()
    {
        $this->setupTube('integration-test');
        // make sure it's empty
        $this->beanstalk->peekReady();

        $data = 'This is my data';
        $id = $this->beanstalk->put($data);
        $jobData = $this->beanstalk->reserve();

        $this->assertEquals($id, $jobData['id']);
        $this->assertEquals($data, $jobData['body']);

        $this->beanstalk->touch($jobData['id']);
        $this->beanstalk->delete($jobData['id']);

        $this->assertFalse($this->beanstalk->peekReady());
    }

    public function testBuriedJobProcess()
    {
        $this->setupTube('integration-test');
        try {
            // make sure it's empty
            $this->beanstalk->peekReady();
        } catch (NotFoundException $e) {
            // continue
        }
        $data = 'This is my data';
        $id = $this->beanstalk->put($data);
        $jobData = $this->beanstalk->reserve();

        $this->assertEquals($id, $jobData['id']);
        $this->assertEquals($data, $jobData['body']);

        $this->beanstalk->bury($jobData['id']);

        $buriedData = $this->beanstalk->peekBuried();
        $this->assertEquals($jobData['id'], $buriedData['id']);

        $this->beanstalk->kick(1);
        $this->beanstalk->delete($buriedData['id']);
    }

    public function testLargeJobData()
    {
        $this->setupTube('integration-test');

        $length = 8192 * 2;
        $data = str_repeat('.', $length);
        $this->beanstalk->put($data);
        $jobData = $this->beanstalk->reserve();
        $this->beanstalk->delete($jobData['id']);

        $this->assertEquals($length, strlen($jobData['body']));
    }

    public function setupTube($tube)
    {
        $this->beanstalk
            ->useTube($tube)
            ->watch($tube)
            ->ignore('default');
    }
}
