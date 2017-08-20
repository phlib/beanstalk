<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Console\Console;

class Mapping
{
    /** @var array */
    private $mapping;

    /** @var int */
    private $column = 0;

    /** @var int */
    private $row = 0;

    /** @var int */
    private $maxColumn = 1;

    /** @var int */
    private $maxRow = 1;

    public function __construct(array $mapping)
    {
        $this->mapping   = $this->filter($mapping);
        $this->maxRow    = count($this->mapping) - 1;
        $this->maxColumn = count($this->mapping[0]) - 1;
    }

    private function filter(array $mapping)
    {
        $filtered = [];
        unset($mapping[0]);
        foreach ($mapping as $rowIdx => $row) {
            $newRow = [];
            $newRow[] = $row[2] + [
                'select' => [
                    'action' => 'manage-jobs',
                    'args'   => [$row[0]['word'], 'ready']
                ]
            ];
            $newRow[] = $row[4] + [
                'select' => [
                    'action' => 'manage-jobs',
                    'args'   => [$row[0]['word'], 'delayed']
                ]
            ];
            $newRow[] = $row[5] + [
                'select' => [
                    'action' => 'manage-jobs',
                    'args'   => [$row[0]['word'], 'buried']
                ]
            ];
            $filtered[] = $newRow;
        }
        return $filtered;
    }

    public function select()
    {
        return $this->mapping[$this->row][$this->column]['select'];
    }

    public function current(): array
    {
        if (!isset($this->mapping[$this->row][$this->column]) ||
            empty($this->mapping[$this->row][$this->column]['xPos']) ||
            empty($this->mapping[$this->row][$this->column]['yPos'])
        ) {
            var_dump($this->row);
            var_dump($this->maxRow);
            var_dump($this->column);
            var_dump($this->maxColumn);
            print_r($this->mapping);
            die;
        }
        return [
            'x'      => $this->mapping[$this->row][$this->column]['xPos'],
            'y'      => $this->mapping[$this->row][$this->column]['yPos'],
            'length' => $this->mapping[$this->row][$this->column]['length']
        ];
    }

    public function moveUp(): ?array
    {
        if ($this->row == 0) {
            return null;
        }
        $this->row--;
        return $this->current();
    }

    public function moveDown(): ?array
    {
        if ($this->row == $this->maxRow) {
            return null;
        }
        $this->row++;
        return $this->current();
    }

    public function moveLeft(): ?array
    {
        if ($this->column == 0) {
            return null;
        }
        $this->column--;
        return $this->current();
    }

    public function moveRight(): ?array
    {
        if ($this->column == $this->maxColumn) {
            return null;
        }
        $this->column++;
        return $this->current();
    }
}
