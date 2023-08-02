<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Model;

/**
 * @package Phlib\Beanstalk
 */
class Stats
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

    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function aggregate(array $newData): self
    {
        $data = $this->data;
        foreach ($newData as $name => $value) {
            if (!array_key_exists($name, $data)) {
                $data[$name] = $value;
                continue;
            }

            if (in_array($name, self::LIST_STATS, true)) {
                if ($data[$name] !== $value) {
                    $data[$name] .= ',' . $value;
                }
            } elseif (in_array($name, self::MAX_STATS, true)) {
                if ($value > $data[$name]) {
                    $data[$name] = $value;
                }
            } else {
                $data[$name] += $value;
            }
        }

        return new self($data);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }
}
