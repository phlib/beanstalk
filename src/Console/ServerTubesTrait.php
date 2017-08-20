<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Console\Service\StatsService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\Beanstalk
 */
trait ServerTubesTrait
{
    private int $tubeCount = 0;

    private function createStatsTable(StatsService $statsService, OutputInterface $output): Table
    {
        $tubes = $statsService->getAllTubeStats();

        $table = new Table($output);
        $table->setHeaders(StatsService::TUBE_HEADER_MAPPING);

        foreach ($tubes as $stats) {
            if ($stats['current-jobs-buried'] > 0) {
                $stats['name'] = "<error>{$stats['name']}</error>";
                $stats['current-jobs-buried'] = "<error>{$stats['current-jobs-buried']}</error>";
            }
            $table->addRow($stats);
            $this->tubeCount++;
        }

        return $table;
    }
}
