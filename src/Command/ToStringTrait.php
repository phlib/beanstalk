<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

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
