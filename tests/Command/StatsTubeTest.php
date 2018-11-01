<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Tests\Command;

use Phlib\Beanstalk\Command\CommandInterface;
use Phlib\Beanstalk\Command\StatsTube;
use Phlib\Beanstalk\Exception\InvalidArgumentException;

class StatsTubeTest extends CommandTestCase
{
    public function testImplementsCommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new StatsTube('test-tube'));
    }

    public function testGetCommand(): void
    {
        $tube = 'test-tube';
        $this->assertEquals("stats-tube $tube", (new StatsTube($tube))->getCommand());
    }

    public function testTubeIsValidated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StatsTube('');
    }
}
