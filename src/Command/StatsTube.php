<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\ValidateTrait;

/**
 * Class StatsTube
 * @package Phlib\Beanstalk\Command
 */
class StatsTube implements CommandInterface
{
    use StatsTrait;
    use ValidateTrait;

    protected string $tube;

    public function __construct(string $tube)
    {
        $this->validateTubeName($tube);
        $this->tube = $tube;
    }

    public function getCommand(): string
    {
        return sprintf('stats-tube %s', $this->tube);
    }
}
