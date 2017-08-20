<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Console\Console;

class InteractiveInput
{
    const CTRL_UP    = "\033[A";
    const CTRL_DOWN  = "\033[B";
    const CTRL_LEFT  = "\033[D";
    const CTRL_RIGHT = "\033[C";

    const KEY_SPACE  = ' ';
    const KEY_TAB    = "\t";
    const KEY_ENTER  = "\n";

    /** @var array */
    private $intercept = [];

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
    }

    public function intercept($characters, callable $callback)
    {
        if (!is_array($characters)) {
            $characters = [$characters];
        }

        array_walk($characters, function($character) use ($callback) {
            if (!isset($this->intercept[$character])) {
                $this->intercept[$character] = [];
            }
            $this->intercept[$character][] = $callback;
        });
    }

    public function map($mappings)
    {
        foreach ($mappings as $character => $result) {
            $this->intercept($character, function () use ($result) {
                return $result;
            });
        }
    }
}
