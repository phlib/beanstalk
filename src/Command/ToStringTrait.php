<?php

namespace Phlib\Beanstalk\Command;

/**
 * Class ToStringTrait
 * @package Phlib\Beanstalk\Command
 */
trait ToStringTrait
{
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getCommand();
    }
}
