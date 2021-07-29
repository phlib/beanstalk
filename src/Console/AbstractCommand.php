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
    protected ConnectionInterface $beanstalk;

    private StatsService $statsService;

    public function getBeanstalk(): ConnectionInterface
    {
        if (!isset($this->beanstalk)) {
            $config = $this->getHelper('configuration')->fetch();
            $this->beanstalk = Factory::createFromArray($config);
        }

        return $this->beanstalk;
    }

    /**
     * @internal This method is not part of the backward-compatibility promise. Used for DI in unit tests.
     */
    public function setBeanstalk(ConnectionInterface $beanstalk): void
    {
        $this->beanstalk = $beanstalk;
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
