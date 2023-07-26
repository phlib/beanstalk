<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Connection;

/**
 * @package Phlib\Beanstalk
 */
interface SocketInterface
{
    public function write(string $data): self;

    public function read(int $length = null): string;

    public function disconnect(): bool;
}
