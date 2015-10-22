<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerTubesCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('server:tubes')
            ->setDescription('List all tubes known to the server(s).')
            ->addOption('buried', 'b', InputArgument::OPTIONAL, 'Only list tubes which have buried jobs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $beanstalk = $this->getBeanstalk();
        $tubeList  = $beanstalk->listTubes();
        if ($input->getOption('buried')) {
//            $tubesBuried = array();
//            foreach($tubeList as $tube) {
//                $stats = $beanstalk->statsTube($tube);
//                $buried = $stats['current-jobs-buried'];
//                if ($buried > 0) {
//                    $tubesBuried[$tube] = $buried;
//                }
//            }
        }
        $output->writeln(var_export($tubeList, true)); // TODO: this needs to be prettier?
    }
}
