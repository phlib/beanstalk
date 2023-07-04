<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Connection\Socket;

class AbstractCommandTest extends ConsoleTestCase
{
    protected function setUpCommand(): AbstractCommand
    {
        // Use a simple Command as the concrete class for the Abstract
        return new JobDeleteCommand($this->factory);
    }

    public function testHostOption(): void
    {
        $host = sha1(uniqid('host'));

        $expectedConnectionConfig = [
            'host' => $host,
            // No port specified in the options, so the default is expected
            'port' => Socket::DEFAULT_PORT,
        ];

        /**
         * Override the behaviour from
         * @see parent::setUp()
         * to be able to check the passed value for $config
         */
        $this->factory->expects(static::once())
            ->method('createFromArrayBC')
            ->with($expectedConnectionConfig)
            ->willReturn($this->connection);

        // Add the basic command behaviour to avoid exceptions
        $this->connection->expects(static::once())
            ->method('delete')
            ->willReturnSelf();

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'job-id' => rand(),
            '--host' => $host,
        ]);
    }

    public function testPortOption(): void
    {
        $host = sha1(uniqid('host'));
        $port = rand();

        $expectedConnectionConfig = [
            'host' => $host,
            'port' => $port,
        ];

        /**
         * Override the behaviour from
         * @see parent::setUp()
         * to be able to check the passed value for $config
         */
        $this->factory->expects(static::once())
            ->method('createFromArrayBC')
            ->with($expectedConnectionConfig)
            ->willReturn($this->connection);

        // Add the basic command behaviour to avoid exceptions
        $this->connection->expects(static::once())
            ->method('delete')
            ->willReturnSelf();

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'job-id' => rand(),
            '--host' => $host,
            '--port' => $port,
        ]);
    }
}
