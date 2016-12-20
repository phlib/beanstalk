<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\StatsService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractWatchCommandTest extends ConsoleTestCase
{
    /**
     * @var AbstractCommand|MockObject
     */
    protected AbstractCommand $command;

    protected function setUpCommand(): AbstractCommand
    {
        $statsService = $this->createMock(StatsService::class);
        $statsServiceFactory = fn () => $statsService;

        $command = $this->getMockForAbstractClass(
            AbstractWatchCommand::class,
            [$this->factory, $statsServiceFactory],
        );
        $command->setName(sha1(uniqid('command')));

        return $command;
    }

    public function testNoWatchRunsOnce(): void
    {
        $this->command->expects(static::once())
            ->method('foreachWatch')
            ->willReturn(0);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
        ]);
    }

    public function testExitCodeIsUsed(): void
    {
        $exitCode = rand(1, 64);

        $this->command->expects(static::once())
            ->method('foreachWatch')
            ->willReturn($exitCode);

        $actual = $this->commandTester->execute([
            'command' => $this->command->getName(),
        ]);

        self::assertSame($exitCode, $actual);
    }

    /**
     * @medium
     */
    public function testWatchRunsMultiple(): void
    {
        // Only way to stop the loop is to return a non-zero exit code
        $this->command->expects(static::exactly(3))
            ->method('foreachWatch')
            ->willReturnOnConsecutiveCalls(0, 0, 1);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--watch' => true,
        ]);

        $output = $this->commandTester->getDisplay();
    }

    public function testWatchHeaderOutput(): void
    {
        $message = sha1(uniqid('message'));

        // Only way to stop the loop is to return a non-zero exit code
        $this->command->expects(static::once())
            ->method('foreachWatch')
            ->willReturnCallback(function (InputInterface $input, OutputInterface $output) use ($message) {
                $output->writeln($message);
                return 1;
            });

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--watch' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertStringEndsWith($message . "\n", $output);
    }
}
