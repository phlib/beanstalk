<?php

namespace Phlib\Beanstalk\Tests;

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
        return [[''], [null], [str_repeat('.', 201)]];
    }
}
