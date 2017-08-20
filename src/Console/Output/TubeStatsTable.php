<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Console\Output;

use Phlib\Beanstalk\Stats\Service;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class TubeStatsTable
{
    /**
     * @param Service $service
     * @param OutputInterface $output
     * @return Table
     */
    public static function create(Service $service, OutputInterface $output): Table
    {
        $table = new Table($output);
        $table->setHeaders($service->getTubeHeaderMapping());

        $tubes = $service->getAllTubeStats();
        foreach ($tubes as $stats) {
            if ($stats['current-jobs-buried'] > 0) {
                $stats['name'] = "<error>{$stats['name']}</error>";
                $stats['current-jobs-buried'] = "<error>{$stats['current-jobs-buried']}</error>";
            }
            $table->addRow($stats);
        }
        return $table;
    }
}
