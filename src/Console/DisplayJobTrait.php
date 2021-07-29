<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;

trait DisplayJobTrait
{
    protected function displayJob(array $job, OutputInterface $output): void
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');
        $section = $formatter->formatSection("Job ID: {$job['id']}", var_export($job['body'], true));
        $output->writeln($section);
    }
}
