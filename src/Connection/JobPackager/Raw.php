<?php

namespace Phlib\Beanstalk\Connection\JobPackager;

/**
 * Class Raw
 * @package Phlib\Beanstalk\Job
 */
class Raw implements PackagerInterface
{
    /**
     * @param mixed $data
     * @return string
     */
    public function encode($data)
    {
        switch (gettype($data)) {
            case 'array':
                $data = '(Array)';
                break;
            case 'resource':
                $data = '(Resource)';
                break;
            case 'object':
                $data = (method_exists($data, '__toString')) ? (string)$data : '(Object)';
                break;
            default:
                $data = (string)$data;
        }
        return $data;
    }

    /**
     * @param string $serialized
     * @return mixed
     */
    public function decode($serialized)
    {
        return $serialized;
    }
}
