<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\InvalidArgumentException;

/**
 * @package Phlib\Beanstalk\Command
 */
class PeekStatus extends Peek
{
    public const READY = 'ready';

    public const DELAYED = 'delayed';

    public const BURIED = 'buried';

    private const ALLOWED_STATUS = [
        self::READY,
        self::DELAYED,
        self::BURIED,
    ];

    private string $status;

    public function __construct(string $status)
    {
        if (!in_array($status, self::ALLOWED_STATUS, true)) {
            throw new InvalidArgumentException(sprintf('Invalid peek subject: %s', $status));
        }
        $this->status = $status;
    }

    protected function getCommand(): string
    {
        return sprintf('peek-%s', $this->status);
    }
}
