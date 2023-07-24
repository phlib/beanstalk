<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class IntegrationPoolTest extends TestCase
{
    private Pool $beanstalk;

    protected function setUp(): void
    {
        if ((bool)getenv('BSTALK_ENABLED') !== true) {
            static::markTestSkipped();
            return;
        }

        $connections = [
            new Connection(new Socket(getenv('BSTALK1_HOST'), (int)getenv('BSTALK1_PORT'))),
            new Connection(new Socket(getenv('BSTALK2_HOST'), (int)getenv('BSTALK2_PORT'))),
        ];
        $this->beanstalk = new Pool(new Pool\Collection($connections));
    }

    public function testReconnectingAfterDisconnect(): void
    {
        $this->beanstalk->listTubes(); // make sure we connect
        $this->beanstalk->disconnect();

        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        static::assertContains($tube, $this->beanstalk->listTubes());
    }

    public function testStartWithDefaultTube(): void
    {
        static::assertSame('default', $this->beanstalk->listTubeUsed());
    }

    public function testSwitchingUsedTube(): void
    {
        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        static::assertSame($tube, $this->beanstalk->listTubeUsed());
    }

    public function testStartWithDefaultWatching(): void
    {
        static::assertSame(['default'], $this->beanstalk->listTubesWatched());
    }

    public function testWatchingMoreTubes(): void
    {
        $tube = 'test-tube';
        $this->beanstalk->watch($tube);
        static::assertContains($tube, $this->beanstalk->listTubesWatched());
    }

    public function testListTubes(): void
    {
        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        static::assertContains($tube, $this->beanstalk->listTubes());
    }

    public function testFullJobProcess(): void
    {
        $this->setupTube('integration-test');
        // make sure it's empty
        static::assertNull($this->beanstalk->peekReady());

        $data = 'This is my data';
        $id = $this->beanstalk->put($data);
        $jobData = $this->beanstalk->reserve();

        static::assertSame($id, $jobData['id']);
        static::assertSame($data, $jobData['body']);

        $this->beanstalk->touch($jobData['id']);
        $this->beanstalk->delete($jobData['id']);

        static::assertNull($this->beanstalk->peekReady());
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

        static::assertSame($id, $jobData['id']);
        static::assertSame($data, $jobData['body']);

        $this->beanstalk->bury($jobData['id']);

        $buriedData = $this->beanstalk->peekBuried();
        static::assertSame($jobData['id'], $buriedData['id']);

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

        static::assertSame($length, strlen($jobData['body']));
    }

    private function setupTube($tube): void
    {
        $this->beanstalk
            ->useTube($tube)
            ->watch($tube)
            ->ignore('default');
    }
}
