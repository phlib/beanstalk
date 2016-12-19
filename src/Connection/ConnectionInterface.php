<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Connection;

interface ConnectionInterface
{
    const DEFAULT_PRIORITY = 1024;
    const DEFAULT_DELAY    = 0;
    const DEFAULT_TTR      = 60;

    const MAX_JOB_LENGTH  = 65536;      // 2^16
    const MAX_TUBE_LENGTH = 200;
    const MAX_PRIORITY    = 4294967295; // 2^32

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return bool
     */
    public function disconnect(): bool;

    /**
     * @param string $tube
     * @return ConnectionInterface
     */
    public function useTube(string $tube): self;

    /**
     * @param string  $data
     * @param int $priority
     * @param int $delay
     * @param int $ttr
     * @return string|int
     */
    public function put(
        string $data,
        int $priority = self::DEFAULT_PRIORITY,
        int $delay = self::DEFAULT_DELAY,
        int $ttr = self::DEFAULT_TTR
    );

    /**
     * @param int $timeout
     * @return array|false
     */
    public function reserve(int $timeout = null);

    /**
     * @param int|string $id
     * @return ConnectionInterface
     */
    public function touch($id): self;

    /**
     * @param int|string $id
     * @param int $priority
     * @param int $delay
     * @return ConnectionInterface
     */
    public function release(
        $id,
        int $priority = self::DEFAULT_PRIORITY,
        int $delay = self::DEFAULT_DELAY
    ): self;

    /**
     * @param int|string $id
     * @param int $priority
     * @return ConnectionInterface
     */
    public function bury($id, int $priority = self::DEFAULT_PRIORITY): self;

    /**
     * @param int|string $id
     * @return ConnectionInterface
     */
    public function delete($id): self;

    /**
     * @param string $tube
     * @return ConnectionInterface
     */
    public function watch(string $tube): self;

    /**
     * @param string $tube
     * @return int|false Number of tubes being watched or false
     */
    public function ignore(string $tube);

    /**
     * @param int $id
     * @return array
     */
    public function peek($id);

    /**
     * @param int|string $id
     * @return array
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

    /**
     * @param int $quantity
     * @return int
     */
    public function kick(int $quantity): int;

    /**
     * @param string $tube
     * @return array
     */
    public function statsTube(string $tube): string;

    /**
     * @return array
     */
    public function stats(): array;

    /**
     * @return array
     */
    public function listTubes(): array;

    /**
     * @return string
     */
    public function listTubeUsed(): string;

    /**
     * @return array
     */
    public function listTubesWatched(): array;
}
