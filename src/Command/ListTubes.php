<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

class ListTubes implements CommandInterface
{
    use StatsTrait;
    use ToStringTrait;

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return 'list-tubes';
    }
}
