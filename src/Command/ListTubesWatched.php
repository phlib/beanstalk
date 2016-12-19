<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Command;

class ListTubesWatched implements CommandInterface
{
    use StatsTrait;
    use ToStringTrait;

    /**
     * @return string
     */
    public function getCommand()
    {
        return 'list-tubes-watched';
    }
}
