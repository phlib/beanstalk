<?php

namespace Phlib\Beanstalk\Tests\JobPackager;

use Phlib\Beanstalk\Connection\JobPackager\Php;
use phpmock\phpunit\PHPMock;

class PhpTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    public function testImplementsInterface()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Connection\JobPackager\PackagerInterface', new Php());
    }

    public function testEncodeMethodCalled()
    {
        $inputData = ['foo' => 'bar', 'bar' => 'baz'];
        $serialize = $this->getFunctionMock('\Phlib\Beanstalk\Connection\JobPackager', 'serialize');
        $serialize->expects($this->once())
            ->with($this->equalTo($inputData));

        (new Php())->encode($inputData);
    }

    public function testDecodeMethodCalled()
    {
        $inputData = 'a:2:{s:3:"foo";s:3:"bar";s:3:"bar";s:3:"baz";}';
        $unserialize = $this->getFunctionMock('\Phlib\Beanstalk\Connection\JobPackager', 'unserialize');
        $unserialize->expects($this->once())
            ->with($this->equalTo($inputData));

        (new Php())->decode($inputData);
    }
}
