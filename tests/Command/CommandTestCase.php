<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Connection\Socket;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommandTestCase extends TestCase
{
    /**
     * @var Socket|MockObject
     */
    protected Socket $socket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->socket = $this->createMock(Socket::class);
    }
}
