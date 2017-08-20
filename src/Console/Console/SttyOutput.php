<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console\Console;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class SttyOutput implements OutputInterface
{
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
        $this->output->write("\033[2J\033[H");
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
