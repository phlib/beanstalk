<?php

namespace Phlib\Beanstalk\Tests\JobPackager;

use Phlib\Beanstalk\Connection\JobPackager\Json;
use phpmock\phpunit\PHPMock;

class JsonTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    public function testImplementsInterface()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Connection\JobPackager\PackagerInterface', new Json());
    }

    public function testEncodeMethodCalled()
    {
        $inputData = ['foo' => 'bar', 'bar' => 'baz'];
        $this->assertInternalType('string', (new Json())->encode($inputData));
    }

    public function testDecodeMethodCalled()
    {
        $inputData = '{"foo":"bar","bar":"baz"}';
        $this->assertInternalType('array', (new Json())->decode($inputData));
    }

    public function testAssocArrayIsDecodedToArrayNotStdClass()
    {
        $inputData = '{"foo":"bar","bar":"baz"}';
        $this->assertInternalType('array', (new Json())->decode($inputData));
    }
}
