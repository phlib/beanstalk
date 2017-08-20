<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console\Console;

class Mapping
{
    private array $mapping;

    private int $column = 0;

    private int $row = 0;

    private int $maxColumn;

    private int $maxRow;

    public function __construct(array $mapping)
    {
        $this->mapping = $this->filter($mapping);
        $this->maxRow = count($this->mapping) - 1;
        $this->maxColumn = count($this->mapping[0]) - 1;
    }

    private function filter(array $mapping): array
    {
        $filtered = [];
        unset($mapping[0]);
        foreach ($mapping as $rowIdx => $row) {
            $newRow = [];
            $newRow[] = $row[2] + [
                'select' => [
                    'action' => 'manage-jobs',
                    'args' => [$row[0]['word'], 'ready'],
                ],
            ];
            $newRow[] = $row[4] + [
                'select' => [
                    'action' => 'manage-jobs',
                    'args' => [$row[0]['word'], 'delayed'],
                ],
            ];
            $newRow[] = $row[5] + [
                'select' => [
                    'action' => 'manage-jobs',
                    'args' => [$row[0]['word'], 'buried'],
                ],
            ];
            $filtered[] = $newRow;
        }
        return $filtered;
    }

    public function select(): array
    {
        return $this->mapping[$this->row][$this->column]['select'];
    }

    public function current(): array
    {
        return [
            'x' => $this->mapping[$this->row][$this->column]['xPos'],
            'y' => $this->mapping[$this->row][$this->column]['yPos'],
            'length' => $this->mapping[$this->row][$this->column]['length'],
            'word' => $this->mapping[$this->row][$this->column]['word'],
        ];
    }

    public function moveUp(): ?array
    {
        if ($this->row === 0) {
            return null;
        }
        $this->row--;
        return $this->current();
    }

    public function moveDown(): ?array
    {
        if ($this->row === $this->maxRow) {
            return null;
        }
        $this->row++;
        return $this->current();
    }

    public function moveLeft(): ?array
    {
        if ($this->column === 0) {
            return null;
        }
        $this->column--;
        return $this->current();
    }

    public function moveRight(): ?array
    {
        if ($this->column === $this->maxColumn) {
            return null;
        }
        $this->column++;
        return $this->current();
    }
}
