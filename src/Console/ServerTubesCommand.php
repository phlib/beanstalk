<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Console\Service\StatsService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\Beanstalk
 */
class ServerTubesCommand extends AbstractWatchCommand
{
    protected function configure(): void
    {
        $this->setName('server:tubes')
            ->setDescription('List all tubes known to the server(s).');

        parent::configure();
    }

    protected function foreachWatch(InputInterface $input, OutputInterface $output): int
    {
        $tubes = $this->getStatsService()->getAllTubeStats();

        if (empty($tubes)) {
            $output->writeln('No tubes found.');
            return 1;
        }

        $table = new Table($output);
        $table->setHeaders(StatsService::TUBE_HEADER_MAPPING);
        foreach ($tubes as $stats) {
            if ($stats['current-jobs-buried'] > 0) {
                $stats['name'] = "<error>{$stats['name']}</error>";
                $stats['current-jobs-buried'] = "<error>{$stats['current-jobs-buried']}</error>";
            }
            $table->addRow($stats);
        }
        $table->render();

        return 0;
    }
}
