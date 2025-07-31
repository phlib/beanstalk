<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Connection;

use Phlib\Beanstalk\Exception\SocketException;

/**
 * @package Phlib\Beanstalk
 * @internal This class is not part of the backward-compatibility promise.
 */
class Socket
{
    public const DEFAULT_PORT = 11300;

    public const EOL = "\r\n";

    private const READ_LENGTH = 4096;

    private string $host;

    private int $port;

    private array $options;

    /**
     * @var resource
     */
    private $connection;

    public function __construct(string $host, int $port = self::DEFAULT_PORT, array $options = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->options = $options + [
            'timeout' => 60,
        ];
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    private function connect(): void
    {
        if (!$this->connection) {
            $errNum = $errStr = null;
            $this->connection = @fsockopen($this->host, $this->port, $errNum, $errStr, $this->options['timeout']);

            if (!$this->connection || $errNum > 0) {
                $message = sprintf(
                    'Could not connect to beanstalkd "%s:%d": %s (%d)',
                    $this->host,
                    $this->port,
                    $errStr,
                    $errNum
                );
                throw new SocketException($message);
            }

            // remove timeout on the stream, allows blocking reserve
            stream_set_timeout($this->connection, -1, 0);
        }
    }

    public function write(string $data): void
    {
        $this->connect();

        $data .= self::EOL;
        $bytesWritten = strlen($data);
        $bytesSent = fwrite($this->connection, $data, $bytesWritten);

        if ($bytesSent !== $bytesWritten) {
            $this->disconnect();
            throw new SocketException('Failed to write data');
        }
    }

    public function read(?int $length = null): string
    {
        $this->connect();

        if ($length) {
            $read = 0;
            $data = '';
            while ($read < $length && !feof($this->connection)) {
                $chunk = fread($this->connection, $length - $read);
                if ($chunk === false) {
                    throw new SocketException('Failed to read data');
                }
                $read += strlen($chunk);
                $data .= $chunk;
            }
        } else {
            $data = stream_get_line($this->connection, self::READ_LENGTH, self::EOL);
            if ($data === false) {
                $this->disconnect();
                throw new SocketException('Failed to read data');
            }
        }

        return $data;
    }

    public function disconnect(): bool
    {
        if (is_resource($this->connection)) {
            fclose($this->connection);
            $this->connection = null;
        }
        return true;
    }
}
