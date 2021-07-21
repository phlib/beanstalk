<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Connection;

/**
 * Interface SocketInterface
 * @package Phlib\Beanstalk
 */
interface SocketInterface
{
    public function getUniqueIdentifier(): string;

    public function write(string $data): self;

    public function read(int $length = null): string;

    public function disconnect(): bool;
}
