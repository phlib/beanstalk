<?php

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\ValidateTrait;
use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class ValidateTraitTest extends \PHPUnit_Framework_TestCase
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

    public function testValidPriority()
    {
        $this->assertTrue($this->validate->validatePriority(123));
    }

    /**
     * @param mixed $priority
     * @dataProvider invalidPriorityDataProvider
     */
    public function testInvalidPriority($priority)
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->validate->validatePriority($priority);
    }

    public function invalidPriorityDataProvider()
    {
        return [[-123], [-1], [ConnectionInterface::MAX_PRIORITY + 1]];
    }

    public function testPriorityOnlyAcceptsIntegers()
    {
        $this->setExpectedException(\TypeError::class);
        $this->validate->validatePriority('string');
    }

    public function testValidTubeName()
    {
        $this->assertTrue($this->validate->validateTubeName('mytube'));
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
        return [[''], [str_repeat('.', ConnectionInterface::MAX_TUBE_LENGTH + 1)]];
    }

    /**
     * @param mixed $data
     * @dataProvider validJobDataDataProvider
     */
    public function testValidJobData($data)
    {
        $this->assertTrue($this->validate->validateJobData($data));
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
