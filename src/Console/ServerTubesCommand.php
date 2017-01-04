<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Stats\Service;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerTubesCommand extends AbstractCommand
{
    use WatchTrait;

    /**
     * @var Service
     */
    protected $service;

    protected function configure()
    {
        $this->setName('server:tubes')
            ->setDescription('List all tubes known to the server(s).')
            ->addOption('watch', null, null, 'Watch server values by refreshing stats every second');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->service = new Service($this->getBeanstalk());
        $this->watch($input, $output, [$this, 'buildTable']);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function buildTable(InputInterface $input, OutputInterface $output)
    {
        $tubes = $this->service->getAllTubeStats();
        $table = new Table($output);
        $table->setHeaders($this->service->getTubeHeaderMapping());
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
