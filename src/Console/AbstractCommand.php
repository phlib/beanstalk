<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Phlib\Beanstalk\Factory;

/**
 * Class AbstractCommand
 * @package Phlib\Beanstalk\Console
 */
abstract class AbstractCommand extends Command
{
    use ConfigurableCommandTrait;

    /**
     * @var \Phlib\Beanstalk\BeanstalkInterface
     */
    protected $beanstalk;

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->loadConfiguration($input);
    }

    /**
     * @return \Phlib\Beanstalk\BeanstalkInterface
     */
    public function getBeanstalk()
    {
        if (!$this->beanstalk) {
            $factory = new Factory;
            $config = $this->getConfiguration();
            if ($config === false) {
                $this->beanstalk = $factory->create('localhost');
            } else {
                $this->beanstalk = $factory->createFromArray($config);
            }
        }

        return $this->beanstalk;
    }
}
