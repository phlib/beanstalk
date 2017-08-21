<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Console\Console;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class SttyOutput implements OutputInterface
{
    const ET_BEGINNING_OF_SCREEN = "1J";
    const ET_END_OF_SCREEN = "J";
    const ET_BEGINNING_OF_LINE = "1K";
    const ET_END_OF_LINE = "K";

    const COLOR_BLACK = 0;
    const COLOR_RED = 1;
    const COLOR_GREEN = 2;
    const COLOR_YELLOW = 3;
    const COLOR_BLUE = 4;
    const COLOR_MAGENTA = 5;
    const COLOR_CYAN = 6;
    const COLOR_WHITE = 7;
    const COLOR_DEFAULT = 9;

    /** @var ConsoleOutput */
    private $output;

    private $previousMode;

    private $xPos = 1;
    private $yPos = 1;
    private $wordMapping = [];

    public function __construct(ConsoleOutput $output)
    {
        $this->output = $output;

        $this->previousMode = shell_exec('stty -g');
        // Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
        shell_exec('stty -icanon -echo');
    }

    public function write($messages, $newline = false, $options = 0)
    {
        $this->output->write($messages, $newline, $options);
    }

    public function writeln($messages, $options = 0)
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }
        foreach ($messages as $message) {
            // remove non-viewable markup
            $message = preg_replace('#<(\w+)>([^<]+?)</\1>#', '\2', $message);
            if (preg_match_all('/(?: ([\w-_]+) )+/', $message, $matches)) {
                $this->wordMapping[] = $this->parseLine($message, $matches[1]);
            }
            $this->yPos++;
        }

        $this->output->writeln($messages, $options);
    }

    private function parseLine($message, $cellContents)
    {
        $rowMapping    = [];
        $charPosition  = 0;
        $cellPosition  = 0;
        $messageLength = strlen($message);
        do {
            $cellWord       = $cellContents[$cellPosition];
            $cellWordLength = strlen((string)$cellWord);
            $lineWord       = substr($message, $charPosition, $cellWordLength);
            if ($lineWord == $cellWord) {
                $rowMapping[$cellPosition] = [
                    'word'   => $cellWord,
                    'xPos'   => $charPosition + 1,
                    'yPos'   => $this->yPos,
                    'length' => $cellWordLength,
                ];
                $charPosition += $cellWordLength;
                $cellPosition++;

                if (!isset($cellContents[$cellPosition])) {
                    break;
                }
            } else {
                $charPosition++;
            }
        } while ($charPosition < $messageLength);

        return $rowMapping;
    }

    public function getMapping()
    {
        return new Mapping($this->wordMapping);
    }

    public function setVerbosity($level)
    {
        $this->output->setVerbosity($level);
    }

    public function getVerbosity()
    {
        return $this->output->getVerbosity();
    }

    public function isQuiet()
    {
        return $this->output->isQuiet();
    }

    public function isVerbose()
    {
        return $this->output->isVerbose();
    }

    public function isVeryVerbose()
    {
        return $this->output->isVerbose();
    }

    public function isDebug()
    {
        return $this->output->isDebug();
    }

    public function setDecorated($decorated)
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated()
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter()
    {
        return $this->output->getFormatter();
    }

    public function clearScreen()
    {
        $this->xPos = $this->yPos = 1;
        $this->command(['[2J', '[H']);
    }

    public function clearLine()
    {
        $this->command(['[2K','[G']);
    }

    public function eraseTo($point = self::ET_END_OF_LINE)
    {
        $this->command("[{$point}");
    }

    public function issueBell()
    {
        $this->output->write(chr(7));
    }

    public function moveCursorHome()
    {
        $this->command('[1;1H');
    }

    public function moveCursor($x, $y)
    {
        $this->command("[{$y};{$x}H");
    }

    public function makeCursorVisible()
    {
        $this->command('[?25h');
    }

    public function makeCursorInvisible()
    {
        $this->command('[?25l');
    }

    public function command($value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        $this->output->write("\e" . implode("\e", $value));
    }

    public function highlight(string $word, int $bgColor, int $fgColor)
    {
        $this->command(["[4{$bgColor};3{$fgColor}m{$word}", '[49;39m']);
    }

    public function getCursor()
    {
        return [$this->xPos, $this->yPos];
    }

    public function __destruct()
    {
        if ($this->previousMode) {
            // Reset stty so it behaves normally again
            shell_exec(sprintf('stty %s', $this->previousMode));
        }
    }
}
