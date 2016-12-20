<?php

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Pool;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerDistribCommand extends AbstractCommand
{
    use WatchTrait;

    /**
     * @var ConnectionInterface[]
     */
    protected $connections;

    protected function configure()
    {
        $this->setName('server:distrib')
            ->setDescription('View distribution of jobs across the servers (for Pools only).')
            ->addOption('tube', 't', InputOption::VALUE_REQUIRED, 'Specify a specific tube.')
            ->addOption('watch', null, null, 'Watch server values by refreshing stats every second');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->connections = $this->getBeanstalk()->getConnections();
        $this->watch($input, $output, [$this, 'buildTable']);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function buildTable(InputInterface $input, OutputInterface $output)
    {
        $headers = ['Stat'];
        $rows = [
            0 => ['workers-watching'],
            1 => ['workers-waiting'],
            2 => ['jobs-urgent'],
            3 => ['jobs-ready'],
            4 => ['jobs-reserved'],
            5 => ['jobs-delayed'],
            6 => ['jobs-buried'],
        ];
        foreach ($this->connections as $connection) {
            $headers[] = $connection->getName();
            $useTube = $input->getOption('tube');
            if ($useTube) {
                $currentWorkers = 'current-watching';
                $stats = $connection->statsTube($useTube);
            } else {
                $currentWorkers = 'current-workers';
                $stats = $connection->stats();
            }

            $rows[0][] = $stats[$currentWorkers];
            $rows[2][] = $stats['current-jobs-urgent'];
            $rows[3][] = $stats['current-jobs-ready'];
            $rows[4][] = $stats['current-jobs-reserved'];
            $rows[5][] = $stats['current-jobs-delayed'];

            $waiting = $stats['current-waiting'];
            if ($waiting == 0) {
                $waiting = "<comment>$waiting</comment>";
            }
            $rows[1][] = $waiting;

            $buried = $stats['current-jobs-buried'];
            if ($buried > 0) {
                $buried = "<error>$buried</error>";
            }
            $rows[6][] = $buried;
        }


        $table = new Table($output);
        $table->setHeaders($headers);
        $table->addRows($rows);
        $table->render();
    }

    /**
     * @return Pool
     */
    public function getBeanstalk()
    {
        $beanstalk = parent::getBeanstalk();
        if (!$beanstalk instanceof Pool) {
            throw new InvalidArgumentException('Command only works with a pool of beanstalk servers.');
        }
        return $beanstalk;
    }
}
