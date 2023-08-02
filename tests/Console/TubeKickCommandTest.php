<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

class TubeKickCommandTest extends ConsoleTestCase
{
    protected function setUpCommand(): AbstractCommand
    {
        return new TubeKickCommand($this->factory);
    }

    public function testTubeKick(): void
    {
        $tube = sha1(uniqid());
        $quantity = rand();
        $kicked = rand();

        $this->connection->expects(static::once())
            ->method('useTube')
            ->with($tube)
            ->willReturnSelf();

        $this->connection->expects(static::once())
            ->method('kick')
            ->with($quantity)
            ->willReturn($kicked);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'tube' => $tube,
            'quantity' => $quantity,
        ]);

        $output = $this->commandTester->getDisplay();
        static::assertSame("Successfully kicked {$kicked} jobs.\n", $output);
    }
}
