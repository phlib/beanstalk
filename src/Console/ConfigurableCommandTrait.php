<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Input\InputInterface;

/**
 * Class DefaultConfigureTrait
 * @package Phlib\Beanstalk\Console
 */
trait ConfigurableCommandTrait
{
    /**
     * @var mixed
     */
    protected $consoleConfig = null;

    /**
     * @return mixed
     */
    public function getConfiguration()
    {
        if (is_null($this->consoleConfig)) {
            throw new \RuntimeException('Configuration has not been initialized.');
        }
        return $this->consoleConfig;
    }

    protected function loadConfiguration(InputInterface $input)
    {
        if (($path = $input->getOption('config')) !== null) {
            $this->consoleConfig = $this->loadFromSpecificFile($input->getOption('config'));
        } else {
            $this->consoleConfig = $this->loadFromDetectedFile($this->detectFile());
        }
        return $this->consoleConfig;
    }

    protected function detectFile()
    {
        $directories = [getcwd(), getcwd() . DIRECTORY_SEPARATOR . 'config'];
        $configFile = null;
        foreach ($directories as $directory) {
            $configFile = $directory . DIRECTORY_SEPARATOR . 'beanstalk-config.php';
            if (file_exists($configFile)) {
                return $configFile;
            }
        }
        return false;
    }

    protected function loadFromDetectedFile($filePath)
    {
        if ($filePath === false || !is_file($filePath) || !is_readable($filePath)) {
            return false;
        }
        return include_once $filePath;
    }

    protected function loadFromSpecificFile($filePath)
    {
        if (is_dir($filePath)) {
            $filePath = $filePath . DIRECTORY_SEPARATOR . self::DEFAULT_CONFIG_FILENAME;
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException("Specified configuration '$filePath' is not accessible.");
        }

        return include_once $filePath;
    }
}
