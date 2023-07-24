<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\SocketInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommandTestCase extends TestCase
{
    /**
     * @var SocketInterface|MockObject
     */
    protected SocketInterface $socket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->socket = $this->getMockForAbstractClass(SocketInterface::class);
    }
}
