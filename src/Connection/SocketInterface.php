<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Connection;

interface SocketInterface
{
    /**
     * @return string
     */
    public function getUniqueIdentifier(): string;

    /**
     * @param string $data
     * @return SocketInterface
     */
    public function write(string $data): self;

    /**
     * @param integer $length
     * @return string
     */
    public function read(int $length = null): string;

    /**
     * @return bool
     */
    public function disconnect(): bool;
}
