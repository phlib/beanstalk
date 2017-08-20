<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console\Console;

class InteractiveInput
{
    public const CTRL_UP = "\033[A";

    public const CTRL_DOWN = "\033[B";

    public const CTRL_LEFT = "\033[D";

    public const CTRL_RIGHT = "\033[C";

    public const KEY_SPACE = ' ';

    public const KEY_TAB = "\t";

    public const KEY_ENTER = "\n";

    /**
     * @var array
     */
    private $intercept = [];

    /**
     * @return mixed
     */
    public function capture()
    {
        while (!feof(STDIN)) {
            $char = fread(STDIN, 1);
            if ($char === "\033") {
                $char .= fread(STDIN, 2);
            }

            if (isset($this->intercept[$char])) {
                foreach ($this->intercept[$char] as $callable) {
                    $result = call_user_func($callable, $char);
                    if ($result) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    public function intercept($characters, callable $callback): void
    {
        if (!is_array($characters)) {
            $characters = [$characters];
        }

        array_walk($characters, function ($character) use ($callback) {
            if (!isset($this->intercept[$character])) {
                $this->intercept[$character] = [];
            }
            $this->intercept[$character][] = $callback;
        });
    }

    public function map($mappings): void
    {
        foreach ($mappings as $character => $result) {
            $this->intercept($character, function () use ($result) {
                return $result;
            });
        }
    }
}
