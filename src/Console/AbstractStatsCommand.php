<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Factory;
use Phlib\Beanstalk\StatsService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\Beanstalk
 */
abstract class AbstractStatsCommand extends AbstractCommand
{
    private \Closure $statsServiceFactory;

    private StatsService $statsService;

    final public function __construct(Factory $factory, \Closure $statsServiceFactory)
    {
        $this->statsServiceFactory = $statsServiceFactory;

        parent::__construct($factory);
    }

    final protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->statsService = ($this->statsServiceFactory)($this->getBeanstalk());
    }

    final protected function getStatsService(): StatsService
    {
        return $this->statsService;
    }
}
