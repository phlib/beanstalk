<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\Beanstalk
 */
abstract class AbstractWatchCommand extends AbstractStatsCommand
{
    protected function configure(): void
    {
        $this->addOption('watch', null, null, 'Watch server values by refreshing stats every second');

        parent::configure();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $watch = $input->getOption('watch');
        $buffered = new BufferedOutput($output->getVerbosity(), $output->isDecorated());

        do {
            $return = $this->foreachWatch($input, $buffered);

            $clearScreen = $watch ? "\e[H\e[2J" : '';
            $output->write($clearScreen . $buffered->fetch());

            if ($return > 0) {
                return $return;
            }

            $watch && sleep(1);
        } while ($watch);

        return 0;
    }

    abstract protected function foreachWatch(InputInterface $input, OutputInterface $output): int;
}
