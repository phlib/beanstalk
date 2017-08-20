<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/**
 * @package Phlib\Beanstalk
 */
abstract class AbstractWatchCommand extends AbstractStatsCommand
{
    protected function configure(): void
    {
        $this->addOption(
            'watch',
            null,
            InputOption::VALUE_OPTIONAL,
            'Watch server values by refreshing stats every N seconds. Default 1 second. Minimum 1 second.',
            false,
        );

        parent::configure();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // false = option not supplied; null = option set without value
        $watch = $input->getOption('watch');

        if ($watch === false) {
            return $this->foreachWatch($input, $output);
        }

        $interval = max(1, round((float)$watch, 1));

        $infoText = sprintf(
            'Every %.1fs: beanstalk %s',
            $interval,
            static::getName(),
        );
        // 25 for datetime
        $paddingSize = strlen($infoText) + 25;
        $infoLinePadding = str_pad('', (new Terminal())->getWidth() - $paddingSize, ' ');

        $buffered = new BufferedOutput($output->getVerbosity(), $output->isDecorated());

        do {
            $buffered->writeln($infoText . $infoLinePadding . date('D M d H:i:s Y'));
            $buffered->writeln('');

            $return = $this->foreachWatch($input, $buffered);

            $clearScreen = "\e[H\e[2J";
            $output->write($clearScreen . $buffered->fetch());

            if ($return > 0) {
                return $return;
            }

            usleep((int)round($interval * 1000000, 0));
        } while (true);
    }

    abstract protected function foreachWatch(InputInterface $input, OutputInterface $output): int;
}
