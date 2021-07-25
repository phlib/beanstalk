<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;

trait DisplayJobTrait
{
    /**
     * @param array $job
     * @param OutputInterface $output
     */
    protected function displayJob(array $job, OutputInterface $output)
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');
        $section   = $formatter->formatSection("Job ID: {$job['id']}", var_export($job['body'], true));
        $output->writeln($section);
    }
}
