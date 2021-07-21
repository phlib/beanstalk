<?php

namespace Phlib\Beanstalk\Command;

use Phlib\Beanstalk\Exception\CommandException;
use Phlib\Beanstalk\Exception\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;

class StatsTraitTest extends CommandTestCase
{
    public function testProcessCompletesOnSuccess()
    {
        $stat = $this->getMockStat(['process']);
        $testString = 'my test data';
        $expectedData = [$testString];

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn("OK {$testString}");

        $stat->expects(static::any())
            ->method('decode')
            ->willReturn([$testString]);

        static::assertEquals($expectedData, $stat->process($this->socket));
    }

    public function testWhenStatusNotFound()
    {
        $this->expectException(NotFoundException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('NOT_FOUND');
        $this->getMockStat(['process'])
            ->process($this->socket);
    }

    public function testWhenStatusUnknown()
    {
        $this->expectException(CommandException::class);

        $this->socket->expects(static::any())
            ->method('read')
            ->willReturn('UNKNOWN_STATUS data');
        $this->getMockStat(['process'])
            ->process($this->socket);
    }

    /**
     * @param string $yaml
     * @dataProvider yamlFormatIsDecodedDataProvider
     */
    public function testYamlFormatIsDecoded($yaml, array $expectedOutput)
    {
        $this->socket->expects(static::any())
            ->method('read')
            ->willReturnOnConsecutiveCalls("OK 1234\r\n", "---\n{$yaml}\r\n");
        $stat = $this->getMockStat(['process', 'decode']);
        static::assertEquals($expectedOutput, $stat->process($this->socket));
    }

    public function yamlFormatIsDecodedDataProvider()
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
    public function getMockStat(array $mockFns)
    {
        $availableFns = ['process', 'decode', 'getCommand'];
        return $this->getMockBuilder(StatsTrait::class)
            ->setMethods(array_diff($availableFns, $mockFns))
            ->getMockForTrait();
    }
}
