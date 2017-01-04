<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Model;

/**
 * @package Phlib\Beanstalk
 */
class Stats extends \ArrayObject
{
    private const LIST_STATS = [
        'pid',
        'version',
        'hostname',
        'name',
        'uptime',
        'binlog-current-index',
    ];

    private const MAX_STATS = [
        'timeouts',
        'binlog-max-size',
        'binlog-oldest-index',
    ];

    public function __construct(array $data = [])
    {
        // Hide additional parent constructor parameters
        parent::__construct($data);
    }

    public function aggregate(array $newData): void
    {
        foreach ($newData as $name => $value) {
            if (!isset($this[$name])) {
                $this[$name] = $value;
                continue;
            }

            if (in_array($name, self::LIST_STATS, true)) {
                if ($this[$name] !== $value) {
                    $this[$name] .= ',' . $value;
                }
            } elseif (in_array($name, self::MAX_STATS, true)) {
                if ($value > $this[$name]) {
                    $this[$name] = $value;
                }
            } else {
                $this[$name] += $value;
            }
        }
    }

    public function toArray(): array
    {
        return $this->getArrayCopy();
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }
}
