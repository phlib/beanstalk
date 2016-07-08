<?php

namespace Phlib\Beanstalk\Connection;

use Phlib\Beanstalk\Exception;
use Phlib\Beanstalk\Connection\SocketInterface;

/**
 * Class Socket
 * @package Phlib\Beanstalk
 */
class Socket implements SocketInterface
{
    const DEFAULT_PORT = 11300;
    const EOL          = "\r\n";
    const READ_LENGTH  = 4096;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var integer
     */
    protected $port;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var resource
     */
    protected $connection;

    /**
     * Constructor
     *
     * @param string  $host
     * @param integer $port
     * @param array   $options
     */
    public function __construct($host, $port = self::DEFAULT_PORT, array $options = [])
    {
        $this->host    = $host;
        $this->port    = $port;
        $this->options = $options + ['timeout' => 60];
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @return string
     */
    public function getUniqueIdentifier()
    {
        return "{$this->host}:{$this->port}";
    }

    /**
     * Connect the socket to the beanstalk server.
     *
     * @return $this
     * @throws Exception\SocketException
     */
    public function connect()
    {
        if (!$this->connection) {
            $errNum = $errStr = null;
            $this->connection = @fsockopen($this->host, $this->port, $errNum, $errStr, $this->options['timeout']);

            if (!$this->connection or $errNum > 0) {
                $message = sprintf(
                    'Could not connect to beanstalkd "%s:%d": %s (%d)',
                    $this->host,
                    $this->port,
                    $errStr,
                    $errNum
                );
                throw new Exception\SocketException($message);
            }

            // remove timeout on the stream, allows blocking reserve
            stream_set_timeout($this->connection, -1, 0);
        }

        return $this;
    }

    /**
     * Write to the socket.
     *
     * @param  string $data
     * @return $this
     * @throws Exception\SocketException
     */
    public function write($data)
    {
        $this->connect();

        $data .= self::EOL;
        $bytesWritten = strlen($data);
        $bytesSent    = fwrite($this->connection, $data, $bytesWritten);

        if ($bytesSent !== $bytesWritten) {
            $this->disconnect();
            throw new Exception\SocketException('Failed to write data.');
        }

        return $this;
    }

    /**
     * @param integer|null $length
     * @return string
     * @throws Exception\SocketException
     */
    public function read($length = null)
    {
        $this->connect();

        if ($length) {
            $read = 0;
            $data = '';
            while ($read < $length && !feof($this->connection)) {
                $chunk = fread($this->connection, $length - $read);
                if ($chunk === false) {
                    throw new Exception\SocketException('Failed to read data.');
                }
                $read += strlen($chunk);
                $data .= $chunk;
            }
        } else {
            $data = stream_get_line($this->connection, self::READ_LENGTH, self::EOL);
            if ($data === false) {
                $this->disconnect();
                throw new Exception\SocketException('Failed to read data.');
            }
        }

        return $data;
    }

    /**
     * @return $this
     */
    public function disconnect()
    {
        if (is_resource($this->connection)) {
            fclose($this->connection);
            $this->connection = null;
        }
        return true;
    }
}
