<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait WatchTrait
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param callable $build
     */
    protected function watch(InputInterface $input, OutputInterface $output, callable $build)
    {
        $watch    = $input->getOption('watch');
        $buffered = new BufferedOutput($output->getVerbosity(), $output->isDecorated());
        do {
            call_user_func_array($build, [$input, $buffered]);

            $clearScreen = $watch ? "\e[H\e[2J" : '';
            $output->write($clearScreen . $buffered->fetch());

            $watch && sleep(1);

        } while ($watch);
    }
}
