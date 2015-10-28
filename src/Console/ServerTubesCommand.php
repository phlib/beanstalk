<?php

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\StatsService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerTubesCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('server:tubes')
            ->setDescription('List all tubes known to the server(s).');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = new StatsService($this->getBeanstalk());
        $tubes = $service->getAllTubeStats();

        if (empty($tubes)) {
            $output->writeln('No tubes found.');
            return;
        }

        $table = new Table($output);
        $table->setHeaders($service->getTubeHeaderMapping());
        foreach ($tubes as $stats) {
            if ($stats['current-jobs-buried'] > 0) {
                $stats['name'] = "<error>{$stats['name']}</error>";
                $stats['current-jobs-buried'] = "<error>{$stats['current-jobs-buried']}</error>";
            }
            $table->addRow($stats);
        }

        $table->render();
    }
}
