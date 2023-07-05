<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

abstract class ConsoleTestCase extends TestCase
{
    protected AbstractCommand $command;

    protected CommandTester $commandTester;

    /**
     * @var Factory|MockObject
     */
    protected Factory $factory;

    /**
     * @var Connection|MockObject
     */
    protected MockObject $connection;

    final protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->expects(static::any())
            ->method('getName')
            ->willReturn(sha1(uniqid()));

        $this->factory = $this->createMock(Factory::class);
        $this->factory->method('createFromArrayBC')
            ->willReturn($this->connection);

        $this->command = $this->setUpCommand();

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);

        parent::setUp();
    }

    abstract protected function setUpCommand(): AbstractCommand;
}
