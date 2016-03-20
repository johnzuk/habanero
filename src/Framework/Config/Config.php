<?php
namespace Habanero\Framework\Config;

use Symfony\Component\Yaml\Parser;
use Habanero\Exceptions\NoConfigException;

class Config implements \ArrayAccess
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var string
     */
    protected $mainPath;

    /**
     * @var array
     */
    protected $config;

    /**
     * Config constructor.
     * @param string $mainPath
     */
    public function __construct($mainPath)
    {
        $this->mainPath = $mainPath;
        $this->parser = new Parser();
        $this->loadConfig();
    }

    /**
     * @return string
     */
    public function getMainPath()
    {
        return $this->mainPath;
    }

    /**
     * @return string
     */
    public function getConfigFilePath()
    {
        return $this->mainPath.DIRECTORY_SEPARATOR.'config.yaml';
    }

    /**
     * @return string
     */
    public function getModuleDirPatch()
    {
        return $this->mainPath.DIRECTORY_SEPARATOR.$this->config['module'];
    }

    /**
     * @return string
     */
    public function getAppPath()
    {
        return $this->mainPath.DIRECTORY_SEPARATOR.'app';
    }

    /**
     * @return string
     */
    public function getCachePath()
    {
        return $this->getAppPath().DIRECTORY_SEPARATOR.'cache';
    }

    /**
     * @return string
     */
    public function getViewCachePath()
    {
        return $this->getCachePath().DIRECTORY_SEPARATOR.'view';
    }

    /**
     * @return string
     */
    public function getRouteCachePatch()
    {
        return $this->getCachePath().DIRECTORY_SEPARATOR.'route';
    }

    /**
     * @return \DirectoryIterator
     */
    public function getModulesPaths()
    {
        $directories = new \DirectoryIterator($this-> getModuleDirPatch());
        $directories = new \CallbackFilterIterator($directories, function (\SplFileInfo $directory){
            return $directory->getBasename() != '.' && $directory->getBasename() != '..';
        });

        return $directories;
    }

    /**
     * @return string
     */
    public function getVendorPath()
    {
        return realpath($this->mainPath.'/vendor');
    }

    /**
     * @return \Generator
     */
    public function getEntityPaths()
    {
        foreach ($this->getModulesPaths() as $modulePath) {
            $path = $this->config['module'].DIRECTORY_SEPARATOR.$modulePath.DIRECTORY_SEPARATOR."Entity";
            if (file_exists($path)) {
                yield $path;
            }
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->config[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->config[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->config[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->config[$offset]);
    }


    protected function loadConfig()
    {
        $configFile = $this->getConfigFilePath();
        if (!is_readable($configFile)) {
            throw new NoConfigException(sprintf('No found config file at: %s', $configFile));
        }
        $this->config = $this->parser->parse(file_get_contents($configFile));
    }
}
