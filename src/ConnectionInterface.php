<?php

declare(strict_types=1);

namespace Phlib\Beanstalk;

/**
 * @package Phlib\Beanstalk
 */
interface ConnectionInterface
{
    public const DEFAULT_PRIORITY = 1024;

    public const DEFAULT_DELAY = 0;

    public const DEFAULT_TTR = 60;

    public const MAX_JOB_LENGTH = 65536; // 2^16

    public const MAX_TUBE_LENGTH = 200;

    public const MAX_PRIORITY = 4_294_967_295; // 2^32 - 1

    public const MAX_DELAY = 4_294_967_295; // 2^32 - 1

    public const MAX_TTR = 4_294_967_295; // 2^32 - 1

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

    public function reserve(?int $timeout = null): ?array;

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
     * @return int|null Number of tubes being watched or null when last tube cannot be ignored
     */
    public function ignore(string $tube): ?int;

    /**
     * @param string|int $id
     */
    public function peek($id): array;

    /**
     * @param string|int $id
     */
    public function statsJob($id): array;

    public function peekReady(): ?array;

    public function peekDelayed(): ?array;

    public function peekBuried(): ?array;

    public function kick(int $quantity): int;

    public function statsTube(string $tube): array;

    public function stats(): array;

    public function listTubes(): array;

    public function listTubeUsed(): string;

    public function listTubesWatched(): array;
}
