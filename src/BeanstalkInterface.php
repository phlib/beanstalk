<?php

namespace Phlib\Beanstalk;

/**
 * Interface ConnectionInterface
 * @package Phlib\Beanstalk
 */
interface BeanstalkInterface
{
    const DEFAULT_PRIORITY = 1024;
    const DEFAULT_DELAY = 0;
    const DEFAULT_TTR = 60;

    /**
     * @param string $tube
     * @return $this
     */
    public function useTube($tube);

    /**
     * @param mixed   $data
     * @param integer $priority
     * @param integer $delay
     * @param integer $ttr
     * @return string|integer
     */
    public function put(
        $data,
        $priority = self::DEFAULT_PRIORITY,
        $delay = self::DEFAULT_DELAY,
        $ttr = self::DEFAULT_TTR
    );

    /**
     * @param integer $timeout
     * @return array
     */
    public function reserve($timeout = null);

    /**
     * @param string|integer $id
     * @return $this
     */
    public function touch($id);

    /**
     * @param string|integer $id
     * @param integer        $priority
     * @param integer        $delay
     * @return $this
     */
    public function release($id, $priority = self::DEFAULT_PRIORITY, $delay = self::DEFAULT_DELAY);

    /**
     * @param string|integer $id
     * @param integer        $priority
     * @return $this
     */
    public function bury($id, $priority = self::DEFAULT_PRIORITY);

    /**
     * @param string|integer $id
     * @return $this
     */
    public function delete($id);

    /**
     * @param string $tube
     * @return $this
     */
    public function watch($tube);

    /**
     * @param string $tube
     * @return int|false Number of tubes being watched or false
     */
    public function ignore($tube);

    /**
     * @param string|integer $id
     * @return array
     */
    public function peek($id);

    /**
     * @param string|integer $id
     * @return array
     */
    public function statsJob($id);

    /**
     * @return array|false
     */
    public function peekReady();

    /**
     * @return array|false
     */
    public function peekDelayed();

    /**
     * @return array|false
     */
    public function peekBuried();

    /**
     * @param integer $quantity
     * @return integer
     */
    public function kick($quantity);

    /**
     * @param string $tube
     * @return array
     */
    public function statsTube($tube);

    /**
     * @return array
     */
    public function stats();

    /**
     * @return array
     */
    public function listTubes();

    /**
     * @return string
     */
    public function listTubeUsed();

    /**
     * @return array
     */
    public function listTubesWatched();
}
