<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\Beanstalk
 */
class ServerTubesCommand extends AbstractWatchCommand
{
    use ServerTubesTrait;

    protected function configure(): void
    {
        $this->setName('server:tubes')
            ->setDescription('List all tubes known to the server(s).');

        parent::configure();
    }

    protected function foreachWatch(InputInterface $input, OutputInterface $output): int
    {
        $table = $this->createStatsTable($this->getStatsService(), $output);

        if ($this->tubeCount === 0) {
            $output->writeln('No tubes found.');
            return 1;
        }

        $table->render();

        return 0;
    }
}
