<?php
namespace Habanero\Framework\Routing;

/**
 * Class Routing
 * @package Habanero\Framework\Routing
 */
class Routing
{
    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * @var Route[]
     */
    protected $routing;

    /**
     * @var string
     */
    protected $modulesPath;

    /**
     * Routing constructor.
     * @param LoaderInterface $loader
     * @param string $modulesPath
     */
    public function __construct(LoaderInterface $loader, $modulesPath)
    {
        $this->loader = $loader;
        $this->modulesPath = $modulesPath;
    }

    /**
     * Load routing
     */
    public function load()
    {
        $this->routing = $this->loader->load($this->modulesPath);
    }

    /**
     * @return Route[]
     */
    public function getRouting()
    {
        return $this->routing;
    }

    /**
     * @return string
     */
    public function getModulesPath()
    {
        return $this->modulesPath;
    }
}
