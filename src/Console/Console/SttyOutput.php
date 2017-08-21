<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console\Console;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class SttyOutput implements OutputInterface
{
    public const ET_BEGINNING_OF_SCREEN = '1J';

    public const ET_END_OF_SCREEN = 'J';

    public const ET_BEGINNING_OF_LINE = '1K';

    public const ET_END_OF_LINE = 'K';

    public const COLOR_BLACK = 0;

    public const COLOR_RED = 1;

    public const COLOR_GREEN = 2;

    public const COLOR_YELLOW = 3;

    public const COLOR_BLUE = 4;

    public const COLOR_MAGENTA = 5;

    public const COLOR_CYAN = 6;

    public const COLOR_WHITE = 7;

    public const COLOR_DEFAULT = 9;

    /**
     * @var ConsoleOutput
     */
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

    public function write($messages, $newline = false, $options = 0): void
    {
        $this->output->write($messages, $newline, $options);
    }

    public function writeln($messages, $options = 0): void
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }
        foreach ($messages as $message) {
            // remove non-viewable markup
            $message = preg_replace('#<(\w+)>([^<]+?)</\1>#', '\2', $message);

            if (preg_match_all('/(?: ([\w_-]+) )+/', $message, $matches)) {
                $this->wordMapping[] = $this->parseLine($message, $matches[1]);
            }
            $this->yPos++;
        }

        $this->output->writeln($messages, $options);
    }

    private function parseLine(string $message, array $cellContents): array
    {
        $rowMapping = [];
        $charPosition = 0;
        $cellPosition = 0;
        $messageLength = strlen($message);
        do {
            $cellWord = $cellContents[$cellPosition];
            $cellWordLength = strlen((string)$cellWord);
            $lineWord = substr($message, $charPosition, $cellWordLength);
            if ($lineWord === $cellWord) {
                $rowMapping[$cellPosition] = [
                    'word' => $cellWord,
                    'xPos' => $charPosition + 1,
                    'yPos' => $this->yPos,
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

    public function getMapping(): Mapping
    {
        return new Mapping($this->wordMapping);
    }

    public function setVerbosity(int $level): void
    {
        $this->output->setVerbosity($level);
    }

    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    public function isVeryVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    public function setDecorated(bool $decorated): void
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }

    public function clearScreen(): void
    {
        $this->xPos = $this->yPos = 1;
        $this->command(['[2J', '[H']);
    }

    public function clearLine(): void
    {
        $this->command(['[2K', '[G']);
    }

    public function eraseTo($point = self::ET_END_OF_LINE): void
    {
        $this->command("[{$point}");
    }

    public function issueBell(): void
    {
        $this->output->write(chr(7));
    }

    public function moveCursorHome(): void
    {
        $this->command('[1;1H');
    }

    public function moveCursor($x, $y): void
    {
        $this->command("[{$y};{$x}H");
    }

    public function makeCursorVisible(): void
    {
        $this->command('[?25h');
    }

    public function makeCursorInvisible(): void
    {
        $this->command('[?25l');
    }

    public function command($value): void
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        $this->output->write("\e" . implode("\e", $value));
    }

    public function highlight(string $word, int $bgColor, int $fgColor): void
    {
        $this->command(["[4{$bgColor};3{$fgColor}m{$word}", '[49;39m']);
    }

    public function getCursor(): array
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
