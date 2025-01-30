<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Exception\NotFoundException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class IntegrationPoolTest extends TestCase
{
    use PHPMock;

    private Pool $beanstalk;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Declare a namespaced shuffle function, so its use in this test doesn't block its use in PoolTest
        static::defineFunctionMock(__NAMESPACE__, 'shuffle');
    }

    protected function setUp(): void
    {
        if ((bool)getenv('BSTALK_ENABLED') !== true) {
            static::markTestSkipped();
            return;
        }

        $connections = [
            new Connection(getenv('BSTALK1_HOST'), (int)getenv('BSTALK1_PORT')),
            new Connection(getenv('BSTALK2_HOST'), (int)getenv('BSTALK2_PORT')),
        ];
        $this->beanstalk = new Pool($connections);
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
        $tube1 = 'test-tube-1';
        $actual1 = $this->beanstalk->watch($tube1);
        static::assertSame(2, $actual1);
        static::assertContains($tube1, $this->beanstalk->listTubesWatched());

        $tube2 = 'test-tube-2';
        $actual2 = $this->beanstalk->watch($tube2);
        static::assertSame(3, $actual2);
        static::assertContains($tube2, $this->beanstalk->listTubesWatched());
    }

    public function testListTubes(): void
    {
        $tube = 'test-tube';
        $this->beanstalk->useTube($tube);
        static::assertContains($tube, $this->beanstalk->listTubes());
    }

    public function testFullJobProcess(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::PEEK_STATUS_MSG);
        $this->expectExceptionCode(NotFoundException::PEEK_STATUS_CODE);

        $this->setupTube('integration-test');

        try {
            // make sure it's empty
            $this->beanstalk->peekReady();
            static::fail('peekReady should have no jobs');
        } catch (NotFoundException $e) {
            // expected response
        }

        $data = 'This is my data';
        $id = $this->beanstalk->put($data);

        // Get raw job ID for comparison, as results may come back from either connection to the same server
        $jobId = $this->getJobId($id);

        try {
            $peek = $this->beanstalk->peekReady();
        } catch (NotFoundException $e) {
            // Job should have been found
            static::fail('peekReady should show the job after touch');
        }
        static::assertSame($jobId, $this->getJobId($peek['id']));
        static::assertSame($data, $peek['body']);

        $jobData = $this->beanstalk->reserve();
        static::assertSame($jobId, $this->getJobId($jobData['id']));
        static::assertSame($data, $jobData['body']);

        $this->beanstalk->touch($jobData['id']);
        $this->beanstalk->delete($jobData['id']);

        $this->beanstalk->peekReady();
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

        // Get raw job ID for comparison, as results may come back from either connection to the same server
        $jobId = $this->getJobId($id);

        static::assertSame($jobId, $this->getJobId($jobData['id']));
        static::assertSame($data, $jobData['body']);

        $this->beanstalk->bury($jobData['id']);

        $buriedData = $this->beanstalk->peekBuried();
        static::assertSame($jobId, $this->getJobId($buriedData['id']));

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

    private function setupTube(string $tube): void
    {
        $this->beanstalk->useTube($tube);
        $this->beanstalk->watch($tube);
        $this->beanstalk->ignore('default');
    }

    private function getJobId(string $combinedId): int
    {
        $position = strrpos($combinedId, '.');
        $jobId = (int)substr($combinedId, $position + 1);

        return $jobId;
    }
}
