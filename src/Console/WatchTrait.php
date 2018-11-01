<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

trait WatchTrait
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param callable $build
     * @param float $interval
     */
    protected function watch(InputInterface $input, OutputInterface $output, callable $build, float $interval = 1.0): void
    {
        $infoText = "Every {$interval}s: beanstalk server:tubes";
        // 34 for info, 25 for datetime == 58
        $infoLinePadding = str_pad('', (new Terminal())->getWidth() - 59, ' ');

        $buffered = new BufferedOutput($output->getVerbosity(), $output->isDecorated());
        do {
            $buffered->writeln($infoText. $infoLinePadding . date('D M d H:i:s Y'));
            $buffered->writeln('');
            $build($input, $buffered);

            $clearScreen = "\e[H\e[2J";
            $output->write($clearScreen . $buffered->fetch());

            usleep((int)round($interval * 1000000, 0));
        } while (true);
    }
}
