<?php

namespace Phlib\Beanstalk\JobPackager;

/**
 * Class Json
 * @package Phlib\Beanstalk\Job
 */
class Json implements PackagerInterface
{
    /**
     * @param mixed $data
     * @return string
     */
    public function encode($data)
    {
        return json_encode($data);
    }

    /**
     * @param string $serialized
     * @return mixed
     */
    public function decode($serialized)
    {
        $decoded = json_decode($serialized);
        if ($decoded instanceof \stdClass) {
            $decoded = (array)$decoded;
        }
        return $decoded;
    }
}
