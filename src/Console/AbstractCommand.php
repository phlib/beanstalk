<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Factory;
use Phlib\Beanstalk\StatsService;
use Symfony\Component\Console\Command\Command;

/**
 * Class AbstractCommand
 * @package Phlib\Beanstalk\Console
 */
abstract class AbstractCommand extends Command
{
    private Factory $factory;

    private ConnectionInterface $beanstalk;

    private StatsService $statsService;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;

        parent::__construct();
    }

    protected function getBeanstalk(): ConnectionInterface
    {
        if (!isset($this->beanstalk)) {
            $config = [];
            // Helper will not be defined in unit tests
            if ($this->getHelperSet()->has('configuration')) {
                $config = $this->getHelper('configuration')->fetch();
            }
            $this->beanstalk = $this->factory->createFromArrayBC($config);
        }

        return $this->beanstalk;
    }

    protected function getStatsService(): StatsService
    {
        if (!isset($this->statsService)) {
            $this->statsService = new StatsService($this->getBeanstalk());
        }

        return $this->statsService;
    }

    /**
     * @internal This method is not part of the backward-compatibility promise. Used for DI in unit tests.
     */
    public function setStatsService(StatsService $statsService): void
    {
        $this->statsService = $statsService;
    }
}
