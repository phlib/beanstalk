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
     */
    public function addStats(array $newData)
    {
        foreach ($newData as $name => $value) {
            if (!array_key_exists($name, $this->data)) {
                $this->data[$name] = $value;
                continue;
            }

            if (in_array($name, self::$listStats)) {
                if ($this->data[$name] != $value) {
                    $this->data[$name] .= ',' . $value;
                }
            } elseif (in_array($name, self::$maxStats)) {
                if ($value > $this->data[$name]) {
                    $this->data[$name] = $value;
                }
            } else {
                $this->data[$name] += $value;
            }
        }
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
