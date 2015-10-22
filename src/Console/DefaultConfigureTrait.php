<?php

namespace Phlib\Beanstalk\Console;
use Phlib\Beanstalk\Socket;

/**
 * Class DefaultConfigureTrait
 * @package Phlib\Beanstalk\Console
 */
trait DefaultConfigureTrait
{
    /**
     * @var \Phlib\Beanstalk\BeanstalkInterface
     */
    protected $beanstalk;

    /**
     * @return \Phlib\Beanstalk\BeanstalkInterface
     */
    protected function getBeanstalk()
    {
        return new \Phlib\Beanstalk\Beanstalk(new Socket('localhost'));
//        return new \Phlib\Beanstalk\Beanstalk('10.1.3.4');
    }
}
