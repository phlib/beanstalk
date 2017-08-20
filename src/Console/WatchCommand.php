<?php
declare(strict_types=1);

namespace Phlib\Beanstalk\Console;

use Phlib\Beanstalk\Console\Output\TubeStatsTable;
use Phlib\Beanstalk\Stats\Service;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WatchCommand extends AbstractCommand
{
    use WatchTrait;

    protected function configure()
    {
        $this->setName('watch')
            ->setDescription('Continually updates a table of all tubes and their associated stats')
            ->addOption('interval', 'i', InputOption::VALUE_REQUIRED, 'Specify update interval. Minimum 1 second. Default 1 second. ', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service  = new Service($this->getBeanstalk());
        $interval = round((float)$input->getOption('interval'), 1);

        $buildFn = function (InputInterface $input, OutputInterface $output) use ($service) {
            $table = TubeStatsTable::create($service, $output);
            $table->render();
        };
        $this->watch($input, $output, $buildFn, $interval);
    }
}
