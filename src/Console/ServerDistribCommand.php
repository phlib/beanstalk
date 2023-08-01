<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Exception\RuntimeException;
use Phlib\Beanstalk\Pool;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @package Phlib\Beanstalk
 */
class ServerDistribCommand extends AbstractWatchCommand
{
    protected function configure(): void
    {
        $this->setName('server:distrib')
            ->setDescription('View distribution of jobs across the servers (for Pools only).')
            ->addArgument(
                'tube',
                InputArgument::OPTIONAL,
                'Specify a tube; otherwise show total stats'
            );

        parent::configure();
    }

    protected function foreachWatch(InputInterface $input, OutputInterface $output): int
    {
        $beanstalk = $this->getBeanstalk();
        if (!$beanstalk instanceof Pool) {
            throw new RuntimeException('Command only works with a pool of beanstalk servers');
        }
        $collection = $beanstalk->getCollection();

        $io = new SymfonyStyle($input, $output);
        $io->title('Server Distribution');

        $useTube = $input->getArgument('tube');
        $currentWorkers = 'current-workers';
        $command = 'stats';
        $args = [];
        if ($useTube) {
            $io->section('Tube: ' . $useTube);
            $currentWorkers = 'current-watching';
            $command = 'statsTube';
            $args = [$useTube];
        }

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

        $collection->sendToAll($command, $args, function (array $result) use (&$headers, &$rows, $currentWorkers) {
            $headers[] = $result['connection']->getName();

            $stats = $result['response'];

            $rows[0][] = $stats[$currentWorkers];
            $rows[1][] = $stats['current-waiting'];
            $rows[2][] = $stats['current-jobs-urgent'];
            $rows[3][] = $stats['current-jobs-ready'];
            $rows[4][] = $stats['current-jobs-reserved'];
            $rows[5][] = $stats['current-jobs-delayed'];

            $buried = $stats['current-jobs-buried'];
            if ($buried > 0) {
                $buried = "<error>{$buried}</error>";
            }
            $rows[6][] = $buried;
        });

        $table = $io->createTable();
        $table->setHeaders($headers);
        $table->addRows($rows);
        $table->render();

        return 0;
    }
}
