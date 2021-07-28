<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ConsoleTestCase extends TestCase
{
    protected AbstractCommand $command;

    protected CommandTester $commandTester;

    /**
     * @var Connection|MockObject
     */
    protected MockObject $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->expects(static::any())
            ->method('getName')
            ->willReturn(sha1(uniqid()));

        $this->command->setBeanstalk($this->connection);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);

        parent::setUp();
    }
}
