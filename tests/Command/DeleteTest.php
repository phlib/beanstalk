<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;

class DeleteTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        static::assertInstanceOf(CommandInterface::class, new Delete(123));
    }

    public function testSuccessfulCommand(): void
    {
        $id = rand();

        $this->socket->expects(static::once())
            ->method('write')
            ->with("delete {$id}");

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('DELETED');

        $delete = new Delete($id);
        $delete->process($this->socket);
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
        (new Delete($jobId))->process($this->socket);
    }

    public function testUnknownStatusThrowsException(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_ERROR');
        (new Delete(123))->process($this->socket);
    }
}
