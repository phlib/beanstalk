<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommandTestCase extends TestCase
{
    /**
     * @var SocketInterface|MockObject
     */
    protected $socket;

    protected function setUp()
    {
        parent::setUp();
        $this->socket = $this->getMockForAbstractClass(SocketInterface::class);
    }

    protected function tearDown()
    {
        $this->socket = null;
        parent::tearDown();
    }
}
