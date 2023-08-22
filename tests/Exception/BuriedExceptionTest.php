<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Exception;

use PHPUnit\Framework\TestCase;

class BuriedExceptionTest extends TestCase
{
    public function testStaticCreate(): void
    {
        $jobId = rand();

        $exception = BuriedException::create($jobId);

        $expectedDefaultMessage = 'Server ran out of memory trying to grow the priority queue data structure';

        static::assertSame($jobId, $exception->getJobId());
        static::assertSame($expectedDefaultMessage, $exception->getMessage());
    }

    public function testGetJobId(): void
    {
        $jobId = rand();
        $message = sha1(uniqid('message'));

        $exception = new BuriedException($jobId, $message);

        static::assertSame($jobId, $exception->getJobId());
    }

    public function testDefaultExceptionBehaviour(): void
    {
        $jobId = rand();
        $message = sha1(uniqid('message'));
        $code = rand(1, 1024);
        $previous = new \DomainException(sha1(uniqid('previous')));

        $exception = new BuriedException($jobId, $message, $code, $previous);

        static::assertSame($message, $exception->getMessage());
        static::assertSame($code, $exception->getCode());
        static::assertSame($previous, $exception->getPrevious());
    }
}
