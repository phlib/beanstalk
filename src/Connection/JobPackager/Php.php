<?php

namespace Phlib\Beanstalk\Connection\JobPackager;

/**
 * Class Php
 * @package Phlib\Beanstalk\Job
 */
class Php implements PackagerInterface
{
    /**
     * @param mixed $data
     * @return string
     */
    public function encode($data)
    {
        return serialize($data);
    }

    /**
     * @param string $serialized
     * @return mixed
     */
    public function decode($serialized)
    {
        return unserialize($serialized);
    }
}
