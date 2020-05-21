<?php

namespace Phlib\Beanstalk\Model;


class Stats
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
        $this->data = $data;
    }

    /**
     * @param array $newData
     * @return Stats
     */
    public function aggregate(array $newData)
    {
        $data = $this->data;
        foreach ($newData as $name => $value) {
            if (!array_key_exists($name, $data)) {
                $data[$name] = $value;
                continue;
            }

            if (in_array($name, self::$listStats)) {
                if ($data[$name] != $value) {
                    $data[$name] .= ',' . $value;
                }
            } elseif (in_array($name, self::$maxStats)) {
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
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

}
