<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Console\Console\InteractiveInput;
use Phlib\Beanstalk\Console\Console\SttyOutput;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InteractiveCommand extends AbstractStatsCommand
{
    use ServerTubesTrait;

    protected string $jobFormat = 'unserialize';

    protected function configure(): void
    {
        $this->setName('interactive')
            ->setDescription('Interactively work with jobs in Beanstalk for common tasks.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            do {
                $sttyOutput = new SttyOutput($output);
                $ioHelper = new SymfonyStyle($input, $sttyOutput);

                $sttyOutput->clearScreen();

                $table = $this->createStatsTable($this->getStatsService(), $sttyOutput);
                $selected = $this->menuise($sttyOutput, $table);

                $exit = ($selected['action'] === 'exit');
                if (!$exit) {
                    $method = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $selected['action'])));
                    $args = array_merge([$ioHelper], $selected['args'] ?? []);
                    call_user_func_array([$this, $method], $args);
                }
            } while (!$exit);
        } catch (\Throwable $throwable) {
            $output->writeln('Caught Throwable:');
            $output->writeln($throwable->getMessage());
            $output->writeln($throwable->getFile());
            $output->writeln($throwable->getLine());
            $output->writeln($throwable->getTraceAsString());
        }

        return 0;
    }

    /**
     * @return mixed
     */
    protected function menuise(SttyOutput $output, Table $table)
    {
        $mapping = $output->captureTableOutput($table);

        $output->writeln('[<comment>r</comment>] refresh [<comment>s</comment>] settings [<comment>e</comment>] exit');

        $input = new InteractiveInput();
        $input->map([
            'e' => ['action' => 'exit'],
            'r' => ['action' => 'refresh'],
            's' => ['action' => 'settings'],
        ]);
        $prev = null;
        $input->intercept(
            [InteractiveInput::CTRL_UP, InteractiveInput::CTRL_DOWN, InteractiveInput::CTRL_LEFT, InteractiveInput::CTRL_RIGHT, InteractiveInput::KEY_TAB],
            function ($char) use ($mapping, $output, &$prev) {
                $pos = null;
                switch ($char) {
                    case InteractiveInput::CTRL_UP:
                        $pos = $mapping->moveUp();
                        break;
                    case InteractiveInput::CTRL_DOWN:
                        $pos = $mapping->moveDown();
                        break;
                    case InteractiveInput::CTRL_LEFT:
                        $pos = $mapping->moveLeft();
                        break;
                    case InteractiveInput::CTRL_RIGHT:
                    case InteractiveInput::KEY_TAB:
                        $pos = $mapping->moveRight();
                        break;
                }
                if ($pos !== null) {
                    if ($prev) {
                        $output->moveCursor($prev['x'], $prev['y']);
                        $output->highlight($prev['word'], SttyOutput::COLOR_DEFAULT, SttyOutput::COLOR_DEFAULT);
                    }
                    $output->moveCursor($pos['x'], $pos['y']);
                    $output->highlight($pos['word'], SttyOutput::COLOR_WHITE, SttyOutput::COLOR_BLACK);
                    $prev = $pos;
                }
            }
        );
        $input->intercept(
            [InteractiveInput::KEY_SPACE, InteractiveInput::KEY_ENTER],
            function () use ($mapping) {
                return $mapping->select();
            }
        );

        $pos = $prev = $mapping->current();
        $output->moveCursor($pos['x'], $pos['y']);
        $output->highlight($pos['word'], SttyOutput::COLOR_WHITE, SttyOutput::COLOR_BLACK);

        return $input->capture();
    }

    protected function actionSettings(SymfonyStyle $helper): void
    {
        $helper->write("\e[2J\e[H");
        $this->jobFormat = $helper->choice('Which job format?', ['unserialize', 'json_decode', 'none'], 0);
    }

    protected function actionRefresh(SymfonyStyle $helper): void
    {
        // no-op
    }

    protected function actionManageJobs(SymfonyStyle $helper, string $tube, string $status): void
    {
        $helper->write("\033[2J\033[H");
        $helper->title("{$tube} ({$status})");

        $beanstalk = $this->getBeanstalk();
        $beanstalk->useTube($tube);
        $command = 'peek' . ucfirst($status);

        $jobData = $beanstalk->{$command}();
        if (!$jobData) {
            $helper->block('No jobs to display');
            $helper->write('Press enter to continue.');
            fgets(STDIN);
            return;
        }

        $jobStats = $beanstalk->statsJob($jobData['id']);
        $this->displayJob($jobData, $jobStats, $helper);

        $choices = array_merge($this->getJobChoices($status), ['refresh', 'main-menu']);
        $choice = $helper->choice('Choose what next?', $choices);
        if ($choice === 'delete-all') {
            $confirmed = $helper->confirm('Are you sure you want to delete all jobs?', false);
        }
    }

    protected function getJobChoices($status): array
    {
        $choices = [];
        if (in_array($status, ['delayed', 'buried'], true)) {
            $choices[] = 'kick-one';
            $choices[] = 'kick-quantity';
        }
        return array_merge($choices, ['delete-current', 'delete-all']);
    }

    protected function displayJob(array $jobData, array $jobStats, SymfonyStyle $helper): void
    {
        $timeLeft = $this->secondsToTime($jobStats['time-left']);
        $timeLeft = preg_replace('/0 days, 0 hours, 0 minutes and (\d+ seconds)/', '0 days, 0 hours, 0 minutes and <error>$1</error>', $timeLeft, 1);

        $jobData = $this->decodeJobBody($jobData);
        $jobStats = [
            ['<info>config</info>', ''],
            ['  priority', $jobStats['pri']],
            ['  delay', $this->secondsToMinutes($jobStats['delay'])],
            ['  ttr', $this->secondsToMinutes($jobStats['ttr'])],
            ['', ''],
            ['<info>timing</info>', ''],
            ['  age', $this->secondsToTime($jobStats['age'])],
            ['  time-left', $timeLeft],
            ['', ''],
            ['<info>activity</info>', ''],
            ['  reserves', $jobStats['reserves']],
            ['  timeouts', $jobStats['timeouts']],
            ['  releases', $jobStats['releases']],
            ['  buries', $jobStats['buries']],
            ['  kicks', $jobStats['kicks']],
        ];
        if (is_array($jobData)) {
            $this->displayArrayJob($jobData, $helper);
        } else {
            if (!is_string($jobData)) {
                $jobData = var_export($jobData, true);
            }
            $helper->writeln($jobData);
        }
        $helper->newLine();
        $helper->table([], $jobStats);
    }

    protected function decodeJobBody($jobData): array
    {
        switch ($this->jobFormat) {
            case 'json_decode':
                $jobData['body'] = json_decode($jobData['body'], true);
                break;
            case 'unserialize':
                $jobData['body'] = unserialize($jobData['body']);
                break;
        }
        return $jobData;
    }

    protected function secondsToTime($seconds): string
    {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@{$seconds}");
        return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
    }

    protected function secondsToMinutes($seconds): string
    {
        $minutes = floor($seconds / 60);
        $remaining = $seconds % 60;

        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;

        $output = '';
        if ($hours > 0) {
            $output = "{$hours} hour";
            if ($hours > 1) {
                $output .= 's';
            }
            $output .= ' ';
        }

        if ($minutes > 0) {
            $output = "{$minutes} minute";
            if ($minutes > 1) {
                $output .= 's';
            }
            $output .= ' ';
        }

        if ($remaining > 0) {
            if ($minutes > 0) {
                $output .= 'and ';
            }
            $output .= "{$remaining} second";
            if ($remaining > 1) {
                $output .= 's';
            }
        }
        return $output;
    }

    protected function displayArrayJob(array $data, SymfonyStyle $output, $depth = 0): void
    {
        foreach ($data as $key => $value) {
            $padding = str_pad(' ', $depth * 4, ' ');
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $output->writeln("{$padding}{$key}: {");
                }
                $this->displayArrayJob($value, $output, $depth + 1);
                if (!is_numeric($key)) {
                    $output->writeln("{$padding}}");
                }
            } else {
                $export = var_export($value, true);
                switch (gettype($value)) {
                    case 'boolean':
                    case 'NULL':
                        $export = "<fg=yellow>{$export}</>";
                        break;
                    case 'integer':
                    case 'double':
                        $export = "<fg=red>{$export}</>";
                        break;
                    case 'string':
                        $export = "<fg=green>{$export}</>";
                        break;
                }
                $output->writeln($padding . "{$key}: {$export}");
            }
        }
    }

    //    private function autocomplete(OutputInterface $output): void
    //    {
    //        $inputStream = STDIN;
    //        $autocomplete = ['value1', 'value2', 'value3'];
    //        $ret = '';
    //
    //        $i = 0;
    //        $ofs = -1;
    //        $matches = $autocomplete;
    //        $numMatches = count($matches);
    //
    //        $sttyMode = shell_exec('stty -g');
    //
    //        // Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
    //        shell_exec('stty -icanon -echo');
    //
    //        // Add highlighted text style
    //        $output->getFormatter()->setStyle('hl', new OutputFormatterStyle('black', 'white'));
    //
    //        // Read a keypress
    //        while (!feof($inputStream)) {
    //            $c = fread($inputStream, 1);
    //
    //            // Backspace Character
    //            if ("\177" === $c) {
    //                if (0 === $numMatches && 0 !== $i) {
    //                    --$i;
    //                    // Move cursor backwards
    //                    $output->write("\033[1D");
    //                }
    //
    //                if ($i === 0) {
    //                    $ofs = -1;
    //                    $matches = $autocomplete;
    //                    $numMatches = count($matches);
    //                } else {
    //                    $numMatches = 0;
    //                }
    //
    //                // Pop the last character off the end of our string
    //                $ret = substr($ret, 0, $i);
    //            } elseif ("\033" === $c) {
    //                // Did we read an escape sequence?
    //                $c .= fread($inputStream, 2);
    //
    //                // A = Up Arrow. B = Down Arrow
    //                if (isset($c[2]) && ('A' === $c[2] || 'B' === $c[2])) {
    //                    if ('A' === $c[2] && -1 === $ofs) {
    //                        $ofs = 0;
    //                    }
    //
    //                    if (0 === $numMatches) {
    //                        continue;
    //                    }
    //
    //                    $ofs += ('A' === $c[2]) ? -1 : 1;
    //                    $ofs = ($numMatches + $ofs) % $numMatches;
    //                }
    //            } elseif (ord($c) < 32) {
    //                if ("\t" === $c || "\n" === $c) {
    //                    if ($numMatches > 0 && -1 !== $ofs) {
    //                        $ret = $matches[$ofs];
    //                        // Echo out remaining chars for current match
    //                        $output->write(substr($ret, $i));
    //                        $i = strlen($ret);
    //                    }
    //
    //                    if ("\n" === $c) {
    //                        $output->write($c);
    //                        break;
    //                    }
    //
    //                    $numMatches = 0;
    //                }
    //
    //                continue;
    //            } else {
    //                $output->write($c);
    //                $ret .= $c;
    //                ++$i;
    //
    //                $numMatches = 0;
    //                $ofs = 0;
    //
    //                foreach ($autocomplete as $value) {
    //                    // If typed characters match the beginning chunk of value (e.g. [AcmeDe]moBundle)
    //                    if (0 === strpos($value, $ret) && $i !== strlen($value)) {
    //                        $matches[$numMatches++] = $value;
    //                    }
    //                }
    //            }
    //
    //            // Erase characters from cursor to end of line
    //            $output->write("\033[K");
    //
    //            if ($numMatches > 0 && -1 !== $ofs) {
    //                // Save cursor position
    //                $output->write("\0337");
    //                // Write highlighted text
    //                $output->write('<hl>'.substr($matches[$ofs], $i).'</hl>');
    //                // Restore cursor position
    //                $output->write("\0338");
    //            }
    //        }
    //
    //        // Reset stty so it behaves normally again
    //        shell_exec(sprintf('stty %s', $sttyMode));
    //var_dump($ret);
    //        return $ret;
    //    }
}
