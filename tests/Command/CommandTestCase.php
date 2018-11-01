<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use PHPUnit\Framework\TestCase;

class CommandTestCase extends TestCase
{
    /**
     * @var \Phlib\Beanstalk\Connection\SocketInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $socket;

    public function setUp()
    {
        parent::setUp();
        $this->socket = $this->createMock(SocketInterface::class);
    }

    public function tearDown()
    {
        $this->socket = null;
        parent::tearDown();
    }
}
