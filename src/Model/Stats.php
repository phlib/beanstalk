<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Model;

/**
 * Stats keys are defined in the protocol as the response to the `stats` command.
 * The `stats-tube` command uses a subset as marked, plus some additional keys described below.
 *   - current-jobs-urgent [&stats-tube]
 *   - current-jobs-ready [&stats-tube]
 *   - current-jobs-reserved [&stats-tube]
 *   - current-jobs-delayed [&stats-tube]
 *   - current-jobs-buried [&stats-tube]
 *   - cmd-put
 *   - cmd-peek
 *   - cmd-peek-ready
 *   - cmd-peek-delayed
 *   - cmd-peek-buried
 *   - cmd-reserve
 *   - cmd-reserve-with-timeout
 *   - cmd-touch
 *   - cmd-use
 *   - cmd-watch
 *   - cmd-ignore
 *   - cmd-delete [&stats-tube]
 *   - cmd-release
 *   - cmd-bury
 *   - cmd-kick
 *   - cmd-stats
 *   - cmd-stats-job
 *   - cmd-stats-tube
 *   - cmd-list-tubes
 *   - cmd-list-tube-used
 *   - cmd-list-tubes-watched
 *   - cmd-pause-tube [&stats-tube]
 *   - job-timeouts
 *   - total-jobs [&stats-tube]
 *   - max-job-size
 *   - current-tubes
 *   - current-connections
 *   - current-producers
 *   - current-workers
 *   - current-waiting [&stats-tube]
 *   - total-connections
 *   - pid
 *   - version
 *   - rusage-utime
 *   - rusage-stime
 *   - uptime
 *   - binlog-oldest-index
 *   - binlog-current-index
 *   - binlog-max-size
 *   - binlog-records-written
 *   - binlog-records-migrated
 *   - draining
 *   - id
 *   - hostname
 *   - os
 *   - platform
 *
 * Only present in response to `stats-tube`:
 *   - name
 *   - current-using
 *   - current-watching
 *   - pause
 *   - pause-time-left
 *
 * @package Phlib\Beanstalk
 */
class Stats
{
    private const LIST_STATS = [
        'pid',
        'version',
        'uptime',
        'binlog-current-index',
        'draining',
        'id',
        'hostname',
        'os',
        'platform',
        'name',
    ];

    private const MAX_STATS = [
        'max-job-size',
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
