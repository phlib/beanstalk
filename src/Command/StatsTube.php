<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\ValidateTrait;

/**
 * @package Phlib\Beanstalk
 */
class StatsTube implements CommandInterface
{
    use StatsTrait;
    use ValidateTrait;

    private string $tube;

    public function __construct(string $tube)
    {
        $this->validateTubeName($tube);
        $this->tube = $tube;
    }

    private function getCommand(): string
    {
        return sprintf('stats-tube %s', $this->tube);
    }
}
