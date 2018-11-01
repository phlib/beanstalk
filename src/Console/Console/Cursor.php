<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console\Console;

class Cursor
{
    private SttyOutput $output;

    public function __construct(SttyOutput $output)
    {
        $this->output = $output;
    }

    public function moveUp(): void
    {
        $this->doMove($this->output->getMapping()->moveUp());
    }

    public function moveDown(): void
    {
        $this->doMove($this->output->getMapping()->moveDown());
    }

    public function moveLeft(): void
    {
        $this->doMove($this->output->getMapping()->moveLeft());
    }

    public function moveRight(): void
    {
        $this->doMove($this->output->getMapping()->moveRight());
    }

    public function moveTo(int $x, int $y): void
    {
        $this->doMove([
            'y' => $y,
            'x' => $x,
        ]);
    }

    protected function doMove($position): void
    {
        if (!is_array($position)) {
            return;
        }
        $position['y']++;
        $this->output->write("\033[{$position['y']};{$position['x']}H");
    }
}
