<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

/**
 * Class ListTubes
 * @package Phlib\Beanstalk\Command
 */
class ListTubesWatched implements CommandInterface
{
    use StatsTrait;
    use ToStringTrait;

    public function getCommand(): string
    {
        return 'list-tubes-watched';
    }
}
