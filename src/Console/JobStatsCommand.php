<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JobStatsCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('job:stats')
            ->setDescription('List statistics related to a specific job.')
            ->addArgument('job-id', InputArgument::REQUIRED, 'The ID of the job.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobId = $input->getArgument('job-id');
        $stats = $this->getBeanstalk()->statsJob($jobId);
        $output->writeln("<info>[Job ID: {$jobId}]</info>");

        $table = new Table($output);
        $table->setStyle('compact');
        $details = array_intersect_key($stats, array_flip(['tube', 'state']));
        foreach ($details as $detail => $value) {
            if ($detail === 'state' && $value === 'buried') {
                $value = "<error>{$value}</error>";
            }
            $table->addRow([$detail, $value]);
        }
        $table->render();

        $created = time();
        $table = new Table($output);
        $stats = array_diff_key($stats, array_flip(['id', 'tube', 'state']));
        $table->setHeaders(['Stat', 'Value']);
        foreach ($stats as $stat => $value) {
            if ($stat === 'age') {
                $created = time() - (int)$value;
                $dt = date('Y-m-d H:i:s', $created);
                $table->addRow(['created', $dt]);
            } elseif ($stat === 'delay') {
                $dt = date('Y-m-d H:i:s', $created + (int)$value);
                $table->addRow(['scheduled', $dt]);
            }
            $table->addRow([$stat, $value]);
        }
        $table->render();

        return 0;
    }
}
