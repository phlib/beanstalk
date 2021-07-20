<?php

namespace Phlib\Beanstalk\Command;

class StatsTraitTest extends CommandTestCase
{
    public function testProcessCompletesOnSuccess()
    {
        $stat         = $this->getMockStat(['process']);
        $testString   = 'my test data';
        $expectedData = [$testString];

        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("OK $testString");

        $stat->expects($this->any())
            ->method('decode')
            ->willReturn([$testString]);

        $this->assertEquals($expectedData, $stat->process($this->socket));
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testWhenStatusNotFound()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("NOT_FOUND");
        $this->getMockStat(['process'])
            ->process($this->socket);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\CommandException
     */
    public function testWhenStatusUnknown()
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn("UNKNOWN_STATUS data");
        $this->getMockStat(['process'])
            ->process($this->socket);
    }

    /**
     * @param string $yaml
     * @param array $expectedOutput
     * @dataProvider yamlFormatIsDecodedDataProvider
     */
    public function testYamlFormatIsDecoded($yaml, array $expectedOutput)
    {
        $this->socket->expects($this->any())
            ->method('read')
            ->will($this->onConsecutiveCalls("OK 1234\r\n", "---\n$yaml\r\n"));
        $stat = $this->getMockStat(['process', 'decode']);
        $this->assertEquals($expectedOutput, $stat->process($this->socket));
    }

    public function yamlFormatIsDecodedDataProvider()
    {
        return [
            ['- value', [0 => 'value']],
            ["- value1\r\n- value2", [0 => 'value1', 1 => 'value2']],
            ['- 321', [0 => 321]],
            ['key1: value1', ['key1' => 'value1']],
            ["key1: value1\r\nkey2: value2", ['key1' => 'value1', 'key2' => 'value2']],
            ['key1: 123', ['key1' => 123]],
            ["key1: value1\r\nkey2: \r\nkey3: value3", ['key1' => 'value1', 'key2' => '', 'key3' => 'value3']],
        ];
    }

    /**
     * @param array $mockFns
     * @return StatsTrait|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockStat(array $mockFns)
    {
        $availableFns = ['process', 'decode', 'getCommand'];
        return $this->getMockBuilder(StatsTrait::class)
            ->setMethods(array_diff($availableFns, $mockFns))
            ->getMockForTrait();
    }
}
