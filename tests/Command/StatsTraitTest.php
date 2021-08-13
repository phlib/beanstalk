<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;

class StatsTraitTest extends CommandTestCase
{
    public function testWhenStatusNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        $this->getMockStat()
            ->process($this->socket);
    }

    public function testWhenStatusUnknown(): void
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_STATUS data');
        $this->getMockStat()
            ->process($this->socket);
    }

    /**
     * @dataProvider yamlFormatIsDecodedDataProvider
     */
    public function testYamlFormatIsDecoded(string $yaml, array $expectedOutput): void
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturnOnConsecutiveCalls("OK 1234\r\n", "---\n{$yaml}\r\n");
        $stat = $this->getMockStat();
        static::assertSame($expectedOutput, $stat->process($this->socket));
    }

    public function yamlFormatIsDecodedDataProvider(): array
    {
        return [
            [
                '- value',
                [
                    0 => 'value',
                ],
            ],
            [
                "- value1\r\n- value2",
                [
                    0 => 'value1',
                    1 => 'value2',
                ],
            ],
            [
                '- 321',
                [
                    0 => 321,
                ],
            ],
            [
                'key1: value1',
                [
                    'key1' => 'value1',
                ],
            ],
            [
                "key1: value1\r\nkey2: value2",
                [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
            ],
            [
                'key1: 123',
                [
                    'key1' => 123,
                ],
            ],
            [
                "key1: value1\r\nkey2: \r\nkey3: value3",
                [
                    'key1' => 'value1',
                    'key2' => '',
                    'key3' => 'value3',
                ],
            ],
        ];
    }

    /**
     * @return StatsTrait|MockObject
     */
    private function getMockStat(): MockObject
    {
        $mock = $this->getMockBuilder(StatsTrait::class)
            ->addMethods(['getCommand'])
            ->getMockForTrait();

        $mock->method('getCommand')
            ->willReturn('stats');

        return $mock;
    }
}
