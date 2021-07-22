<?php

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Connection\Socket;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @group integration
 */
class IntegrationPoolTest extends TestCase
{
    /**
     * @var Connection
     */
    protected $beanstalk;

    protected function setUp()
    {
        if (getenv('BSTALK_ENABLED') != true) {
            static::markTestSkipped();
            return;
        }

        $connections = [
            new Connection(new Socket(getenv('BSTALK1_HOST'), getenv('BSTALK1_PORT'))),
            new Connection(new Socket(getenv('BSTALK2_HOST'), getenv('BSTALK2_PORT')))
        ];
        $this->beanstalk = new Pool(new Pool\Collection($connections));
    }

    public function testReconnectingAfterDisconnect()
    {
        $this->beanstalk->listTubes(); // make sure we connect
        $this->beanstalk->disconnect();

        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        static::assertContains($tube, $this->beanstalk->listTubes());
    }

    public function testStartWithDefaultTube()
    {
        static::assertEquals('default', $this->beanstalk->listTubeUsed());
    }

    public function testSwitchingUsedTube()
    {
        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        static::assertEquals($tube, $this->beanstalk->listTubeUsed());
    }

    public function testStartWithDefaultWatching()
    {
        static::assertEquals(['default'], $this->beanstalk->listTubesWatched());
    }

    public function testWatchingMoreTubes()
    {
        $tube = 'test-tube';
        $this->beanstalk->watch($tube);
        static::assertContains($tube, $this->beanstalk->listTubesWatched());
    }

    public function testListTubes()
    {
        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        static::assertContains($tube, $this->beanstalk->listTubes());
    }

    public function testFullJobProcess()
    {
        $this->setupTube('integration-test');
        // make sure it's empty
        $this->beanstalk->peekReady();

        $data = 'This is my data';
        $id = $this->beanstalk->put($data);
        $jobData = $this->beanstalk->reserve();

        static::assertEquals($id, $jobData['id']);
        static::assertEquals($data, $jobData['body']);

        $this->beanstalk->touch($jobData['id']);
        $this->beanstalk->delete($jobData['id']);

        static::assertFalse($this->beanstalk->peekReady());
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

        static::assertEquals($id, $jobData['id']);
        static::assertEquals($data, $jobData['body']);

        $this->beanstalk->bury($jobData['id']);

        $buriedData = $this->beanstalk->peekBuried();
        static::assertEquals($jobData['id'], $buriedData['id']);

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

        static::assertEquals($length, strlen($jobData['body']));
    }

    public function setupTube($tube)
    {
        $this->beanstalk
            ->useTube($tube)
            ->watch($tube)
            ->ignore('default');
    }
}
