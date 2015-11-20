<?php

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Connection\ConnectionInterface;

class ValidateTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Phlib\Beanstalk\ValidateTrait
     */
    protected $validate;

    public function setUp()
    {
        $this->validate = $this->getMockForTrait('\Phlib\Beanstalk\ValidateTrait');
        parent::setUp();
    }

    public function testValidPriority()
    {
        $this->assertTrue($this->validate->validatePriority(123));
    }

    /**
     * @param mixed $priority
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     * @dataProvider invalidPriorityDataProvider
     */
    public function testInvalidPriority($priority)
    {
        $this->validate->validatePriority($priority);
    }

    public function invalidPriorityDataProvider()
    {
        return [['string'], [-123], [12.43]];
    }

    public function testValidTubeName()
    {
        $this->assertTrue($this->validate->validateTubeName('mytube'));
    }

    /**
     * @param mixed $name
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     * @dataProvider invalidTubeNameDataProvider
     */
    public function testInvalidTubeName($name)
    {
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
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     * @dataProvider invalidJobDataDataProvider
     */
    public function testInvalidJobData($data)
    {
        $this->validate->validateJobData($data);
    }

    public function invalidJobDataDataProvider()
    {
        return [[''], [str_pad('', ConnectionInterface::MAX_JOB_LENGTH + 1)]];
    }
}
