<?php

namespace Phlib\Beanstalk\Tests\JobPackager;

use Phlib\Beanstalk\JobPackager\Raw;

class RawTest extends \PHPUnit_Framework_TestCase
{
    public function testImplementsInterface()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\JobPackager\PackagerInterface', new Raw());
    }

    /**
     * @param string $expected
     * @param string $method
     * @param mixed $structure
     * @dataProvider methodsUsingStructsDataProvider
     */
    public function testMethodsUsingDifferentTypes($expected, $method, $structure)
    {
        $this->assertEquals($expected, (new Raw())->$method($structure));
    }

    public function methodsUsingStructsDataProvider()
    {
        return [
            ['a:2:{s:3:"foo";s:3:"bar";s:3:"bar";s:3:"baz";}', 'encode', 'a:2:{s:3:"foo";s:3:"bar";s:3:"bar";s:3:"baz";}'],
            ['a:2:{s:3:"foo";s:3:"bar";s:3:"bar";s:3:"baz";}', 'decode', 'a:2:{s:3:"foo";s:3:"bar";s:3:"bar";s:3:"baz";}'],
            ['(Array)', 'encode', ['some', 'values']],
            [['some', 'values'], 'decode', ['some', 'values']],
            ['(Resource)', 'encode', STDOUT],
            ['(Object)', 'encode', new \stdClass()],
            ['1', 'encode', true],
            ['', 'encode', false],
            ['', 'encode', null],
        ];
    }
}
