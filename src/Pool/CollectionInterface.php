<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Pool;

use Phlib\Beanstalk\ConnectionInterface;

/**
 * @package Phlib\Beanstalk
 */
interface CollectionInterface extends \IteratorAggregate
{
    public function getAvailableKeys(): array;

    public function getConnection(string $key): ConnectionInterface;

    public function sendToExact(string $key, string $command, array $arguments = []): array;

    /**
     * @return mixed
     */
    public function sendToOne(string $command, array $arguments = []);

    /**
     * @param callable|null $success {
     *     @param array $result
     *     @return bool continue iteration to other connections
     * }
     * @param callable|null $failure {
     *     @return bool continue iteration to other connections
     * }
     */
    public function sendToAll(
        string $command,
        array $arguments = [],
        callable $success = null,
        callable $failure = null
    ): void;
}
