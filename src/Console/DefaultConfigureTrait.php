<?php

namespace Phlib\Beanstalk\Console;

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
        return new \Phlib\Beanstalk\Beanstalk('localhost');
//        return new \Phlib\Beanstalk\Beanstalk('10.1.3.4');
    }
}
