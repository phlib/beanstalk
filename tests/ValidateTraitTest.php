<?php

namespace Phlib\Beanstalk;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidateTraitTest extends TestCase
{
    /**
     * @var ValidateTrait
     */
    protected $validate;

    protected function setUp()
    {
        $this->validate = $this->getMockForTrait(ValidateTrait::class);
        parent::setUp();
    }

    public function testValidPriority()
    {
        static::assertTrue($this->validate->validatePriority(123));
    }

    /**
     * @param mixed $priority
     * @dataProvider invalidPriorityDataProvider
     */
    public function testInvalidPriority($priority)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validate->validatePriority($priority);
    }

    public function invalidPriorityDataProvider()
    {
        return [['string'], [-123], [12.43]];
    }

    public function testValidTubeName()
    {
        static::assertTrue($this->validate->validateTubeName('mytube'));
    }

    /**
     * @param mixed $name
     * @dataProvider invalidTubeNameDataProvider
     */
    public function testInvalidTubeName($name)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validate->validateTubeName($name);
    }

    public function invalidTubeNameDataProvider()
    {
        return [[''], [null], [str_repeat('.', ConnectionInterface::MAX_TUBE_LENGTH + 1)]];
    }

    /**
     * @param mixed $data
     * @dataProvider validJobDataDataProvider
     */
    public function testValidJobData($data)
    {
        static::assertTrue($this->validate->validateJobData($data));
    }

    public function validJobDataDataProvider()
    {
        return [
            ['Foo Bar Baz'],
            [['my' => 'array']],
            [new \stdClass()],
            [1234]
        ];
    }

    /**
     * @dataProvider invalidJobDataDataProvider
     */
    public function testInvalidJobData($data)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validate->validateJobData($data);
    }

    public function invalidJobDataDataProvider()
    {
        return [[''], [str_pad('', ConnectionInterface::MAX_JOB_LENGTH + 1)]];
    }
}
