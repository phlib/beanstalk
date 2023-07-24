<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateTraitTest extends TestCase
{
    /**
     * @var ValidateTrait|MockObject
     */
    private MockObject $validate;

    protected function setUp(): void
    {
        $this->validate = $this->getMockForTrait(ValidateTrait::class);
        parent::setUp();
    }

    public function testValidPriority(): void
    {
        // No exceptions are thrown
        $this->expectNotToPerformAssertions();

        $this->validate->validatePriority(123);
    }

    /**
     * @param mixed $priority
     * @dataProvider invalidPriorityDataProvider
     */
    public function testInvalidPriority($priority, string $exception): void
    {
        $this->expectException($exception);

        $this->validate->validatePriority($priority);
    }

    public function invalidPriorityDataProvider(): array
    {
        // Priority must be integer between 0 and 4,294,967,295
        return [
            'string' => ['string', \TypeError::class],
            'below zero' => [-123, InvalidArgumentException::class],
            'decimal' => [12.43, \TypeError::class],
            'over max' => [4294967296, InvalidArgumentException::class],
        ];
    }

    public function testValidDelay(): void
    {
        // No exceptions are thrown
        $this->expectNotToPerformAssertions();

        $this->validate->validateDelay(123);
    }

    /**
     * @param mixed $delay
     * @dataProvider invalidPriorityDataProvider
     */
    public function testInvalidDelay($delay, string $exception): void
    {
        $this->expectException($exception);

        $this->validate->validateDelay($delay);
    }

    public function testValidTtr(): void
    {
        // No exceptions are thrown
        $this->expectNotToPerformAssertions();

        $this->validate->validateTtr(123);
    }

    /**
     * @param mixed $ttr
     * @dataProvider invalidPriorityDataProvider
     */
    public function testInvalidTtr($ttr, string $exception): void
    {
        $this->expectException($exception);

        $this->validate->validateTtr($ttr);
    }

    public function testValidTubeName(): void
    {
        static::assertTrue($this->validate->validateTubeName('mytube'));
    }

    /**
     * @dataProvider invalidTubeNameDataProvider
     */
    public function testInvalidTubeName(?string $name, string $exception): void
    {
        $this->expectException($exception);

        $this->validate->validateTubeName($name);
    }

    public function invalidTubeNameDataProvider(): array
    {
        return [
            'empty' => ['', InvalidArgumentException::class],
            'null' => [null, \TypeError::class],
            'too long' => [str_repeat('.', ConnectionInterface::MAX_TUBE_LENGTH + 1), InvalidArgumentException::class],
        ];
    }

    /**
     * @param mixed $data
     * @dataProvider validJobDataDataProvider
     */
    public function testValidJobData($data): void
    {
        static::assertTrue($this->validate->validateJobData($data));
    }

    public function validJobDataDataProvider(): array
    {
        return [
            'string' => ['Foo Bar Baz'],
            'array' => [['my' => 'array']],
            'object' => [new \stdClass()],
            'integer' => [1234],
        ];
    }

    /**
     * @dataProvider invalidJobDataDataProvider
     */
    public function testInvalidJobData($data): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validate->validateJobData($data);
    }

    public function invalidJobDataDataProvider(): array
    {
        return [[''], [str_pad('', ConnectionInterface::MAX_JOB_LENGTH + 1)]];
    }
}
