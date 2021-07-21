<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Connection;

/**
 * Interface ConnectionInterface
 * @package Phlib\Beanstalk
 */
interface ConnectionInterface
{
    public const DEFAULT_PRIORITY = 1024;

    public const DEFAULT_DELAY = 0;

    public const DEFAULT_TTR = 60;

    public const MAX_JOB_LENGTH = 65536; // 2^16

    public const MAX_TUBE_LENGTH = 200;

    public const MAX_PRIORITY = 4294967295; // 2^32

    public function getName(): string;

    public function disconnect(): bool;

    public function useTube(string $tube): self;

    /**
     * @return string|int
     */
    public function put(
        string $data,
        int $priority = self::DEFAULT_PRIORITY,
        int $delay = self::DEFAULT_DELAY,
        int $ttr = self::DEFAULT_TTR
    );

    /**
     * @return array|false
     */
    public function reserve(?int $timeout = null);

    /**
     * @param string|int $id
     */
    public function touch($id): self;

    /**
     * @param string|int $id
     */
    public function release($id, int $priority = self::DEFAULT_PRIORITY, int $delay = self::DEFAULT_DELAY): self;

    /**
     * @param string|int $id
     */
    public function bury($id, int $priority = self::DEFAULT_PRIORITY): self;

    /**
     * @param string|int $id
     */
    public function delete($id): self;

    public function watch(string $tube): self;

    /**
     * @return int|false Number of tubes being watched or false
     */
    public function ignore(string $tube);

    /**
     * @param string|int $id
     */
    public function peek($id): array;

    /**
     * @param string|int $id
     */
    public function statsJob($id): array;

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

    public function kick(int $quantity): int;

    /**
     * @return array|false
     */
    public function statsTube(string $tube);

    /**
     * @return array|false
     */
    public function stats();

    public function listTubes(): array;

    public function listTubeUsed(): string;

    public function listTubesWatched(): array;
}
