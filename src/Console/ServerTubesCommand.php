<?php

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\StatsService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ServerTubesCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('server:tubes')
            ->setDescription('List all tubes known to the server(s).')
            ->addOption('watch', null, null, 'Watch server values by refreshing stats every second');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $watch = $input->getOption('watch');
        $buffered = new BufferedOutput($output->getVerbosity(), $output->isDecorated());
        $service = $this->getStatsService();
        do {
            $tubes = $service->getAllTubeStats();

            if (empty($tubes)) {
                $output->writeln('No tubes found.');
                return 0;
            }

            $table = new Table($buffered);
            $table->setHeaders(StatsService::TUBE_HEADER_MAPPING);
            foreach ($tubes as $stats) {
                if ($stats['current-jobs-buried'] > 0) {
                    $stats['name'] = "<error>{$stats['name']}</error>";
                    $stats['current-jobs-buried'] = "<error>{$stats['current-jobs-buried']}</error>";
                }
                $table->addRow($stats);
            }
            $table->render();

            $clearScreen = $watch ? "\e[H\e[2J" : '';
            $output->write($clearScreen . $buffered->fetch());

            $watch && sleep(1);
        } while ($watch);

        return 0;
    }
}
