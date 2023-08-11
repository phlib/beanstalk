<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

class BuryTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Bury(123, 123));
    }

    public function testSuccessfulCommand(): void
    {
        $id = rand();
        $priority = rand(1, ConnectionInterface::MAX_PRIORITY);

        $this->socket->expects(static::once())
            ->method('write')
            ->with("bury {$id} {$priority}");

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('BURIED');

        $bury = new Bury($id, $priority);
        $bury->process($this->socket);
    }

    public function testNotFoundThrowsException(): void
    {
        $jobId = rand();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(sprintf(NotFoundException::JOB_ID_MSG_F, $jobId));
        $this->expectExceptionCode(NotFoundException::JOB_ID_CODE);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        (new Bury($jobId, 123))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Bury(123, 123))->process($this->socket);
    }
}
