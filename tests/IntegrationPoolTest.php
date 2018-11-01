<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Exception\NotFoundException;
use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Pool;
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

    public function setUp()
    {
        if (!isset($GLOBALS['BSTALK_ENABLED']) || (bool)$GLOBALS['BSTALK_ENABLED'] !== true) {
            $this->markTestSkipped();
        } else {
            $connections = [
                new Connection($GLOBALS['BSTALK1_HOST'], (int)$GLOBALS['BSTALK1_PORT']),
                new Connection($GLOBALS['BSTALK2_HOST'], (int)$GLOBALS['BSTALK2_PORT'])
            ];
            $this->beanstalk = new Pool($connections);
        }
    }

    public function testReconnectingAfterDisconnect(): void
    {
        $this->beanstalk->listTubes(); // make sure we connect
        $this->beanstalk->disconnect();

        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        $this->assertContains($tube, $this->beanstalk->listTubes());
    }

    public function testStartWithDefaultTube(): void
    {
        $this->assertEquals('default', $this->beanstalk->listTubeUsed());
    }

    public function testSwitchingUsedTube(): void
    {
        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        $this->assertEquals($tube, $this->beanstalk->listTubeUsed());
    }

    public function testStartWithDefaultWatching(): void
    {
        $this->assertEquals(['default'], $this->beanstalk->listTubesWatched());
    }

    public function testWatchingMoreTubes(): void
    {
        $tube = 'test-tube';
        $this->beanstalk->watch($tube);
        $this->assertContains($tube, $this->beanstalk->listTubesWatched());
    }

    public function testListTubes(): void
    {
        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        $this->assertContains($tube, $this->beanstalk->listTubes());
    }

    public function testFullJobProcess(): void
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

    public function testBuriedJobProcess(): void
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

    public function testLargeJobData(): void
    {
        $this->setupTube('integration-test');

        $length = 8192 * 2;
        $data = str_repeat('.', $length);
        $this->beanstalk->put($data);
        $jobData = $this->beanstalk->reserve();
        $this->beanstalk->delete($jobData['id']);

        $this->assertEquals($length, strlen($jobData['body']));
    }

    public function setupTube($tube): void
    {
        $this->beanstalk
            ->useTube($tube)
            ->watch($tube)
            ->ignore('default');
    }
}
