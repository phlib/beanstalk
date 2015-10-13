<?php

namespace Phlib\Beanstalk\JobPackager;

/**
 * Interface PackagerInterface
 * @package Phlib\Beanstalk
 */
interface PackagerInterface
{
    /**
     * @param mixed $data
     * @return string
     */
    public function encode($data);

    /**
     * @param string $serialized
     * @return mixed
     */
    public function decode($serialized);
}
