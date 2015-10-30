<?php

namespace Phlib\Beanstalk\Tests\Command;

class CommandTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Phlib\Beanstalk\Connection\SocketInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $socket;

    public function setUp()
    {
        parent::setUp();
        $this->socket = $this->getMockForAbstractClass('\Phlib\Beanstalk\Connection\SocketInterface');
    }

    public function tearDown()
    {
        $this->socket = null;
        parent::tearDown();
    }
}
