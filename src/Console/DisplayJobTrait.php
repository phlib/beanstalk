<?php
declare(strict_types = 1);

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Output\OutputInterface;

trait DisplayJobTrait
{
    /**
     * @param array $job
     * @param OutputInterface $output
     */
    protected function displayJob(array $job, OutputInterface $output): void
    {
        /* @var $formatter \Symfony\Component\Console\Helper\FormatterHelper */
        $formatter = $this->getHelper('formatter');
        $section   = $formatter->formatSection("Job ID: {$job['id']}", var_export($job['body'], true));
        $output->writeln($section);
    }
}
