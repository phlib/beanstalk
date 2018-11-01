<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\ValidateTrait;
use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidateTraitTest extends TestCase
{
    /**
     * @var \Phlib\Beanstalk\Command\ValidateTrait
     */
    protected $validate;

    public function setUp()
    {
        $this->validate = $this->getMockForTrait(ValidateTrait::class);
        parent::setUp();
    }

    public function testValidPriority(): void
    {
        $this->assertTrue($this->validate->validatePriority(123));
    }

    /**
     * @param mixed $priority
     * @dataProvider invalidPriorityDataProvider
     */
    public function testInvalidPriority($priority): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validate->validatePriority($priority);
    }

    public function invalidPriorityDataProvider(): array
    {
        return [[-123], [-1], [ConnectionInterface::MAX_PRIORITY + 1]];
    }

    public function testPriorityOnlyAcceptsIntegers(): void
    {
        $this->expectException(\TypeError::class);
        $this->validate->validatePriority('string');
    }

    public function testValidTubeName(): void
    {
        $this->assertTrue($this->validate->validateTubeName('mytube'));
    }

    /**
     * @param mixed $name
     * @dataProvider invalidTubeNameDataProvider
     */
    public function testInvalidTubeName($name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validate->validateTubeName($name);
    }

    public function invalidTubeNameDataProvider(): array
    {
        return [[''], [str_repeat('.', ConnectionInterface::MAX_TUBE_LENGTH + 1)]];
    }

    /**
     * @param mixed $data
     * @dataProvider validJobDataDataProvider
     */
    public function testValidJobData($data): void
    {
        $this->assertTrue($this->validate->validateJobData($data));
    }

    public function validJobDataDataProvider(): array
    {
        return [
            'string' => ['Foo Bar Baz'],
            'array' => [['my' => 'array']],
            'stdClass' => [new \stdClass()],
            'integer' => [1234]
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
