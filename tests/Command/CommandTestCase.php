<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;

class CommandTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SocketInterface|\PHPUnit_Framework_MockObject_MockObject
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
