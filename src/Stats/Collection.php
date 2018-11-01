<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Stats;

class Collection extends \ArrayObject
{
    /**
     * @var array
     */
    protected static $listStats = ['pid', 'version', 'hostname', 'name', 'uptime', 'binlog-current-index'];

    /**
     * @var array
     */
    protected static $maxStats  = ['timeouts', 'binlog-max-size', 'binlog-oldest-index'];

    /**
     * @var array
     */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * @param array $additional
     * @return Collection
     */
    public function merge(array $additional): Collection
    {
        $data = $this->toArray();
        foreach ($additional as $name => $value) {
            if (!array_key_exists($name, $data)) {
                $data[$name] = $value;
                continue;
            }
            if (\in_array($name, self::$listStats, true)) {
                if ($data[$name] !== $value) {
                    $data[$name] .= ',' . $value;
                }
            } elseif (\in_array($name, self::$maxStats, true)) {
                if ($value > $data[$name]) {
                    $data[$name] = $value;
                }
            } else {
                $data[$name] += $value;
            }
        }
        return new self($data);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }
}
