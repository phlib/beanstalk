<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Console\Output\TubeStatsTable;
use Phlib\Beanstalk\Stats\Service;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerTubesCommand extends AbstractCommand
{
    use WatchTrait;

    /**
     * @var Service
     */
    protected $service;

    protected function configure(): void
    {
        $this->setName('server:tubes')
            ->setDescription('List all tubes known to the server(s).')
            ->addOption('watch', null, null, 'Watch server values by refreshing stats every second');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->service = new Service($this->getBeanstalk());
        if ($input->getOption('watch')) {
            $this->watch($input, $output, [$this, 'buildTable']);
        } else {
            $this->buildTable($output);
        }
    }

    /**
     * @param OutputInterface $output
     */
    private function buildTable(OutputInterface $output): void
    {
        $table = TubeStatsTable::create($this->service, $output);
        $table->render();
    }
}
