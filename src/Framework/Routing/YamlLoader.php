<?php
namespace Habanero\Framework\Routing;
use Symfony\Component\Yaml\Parser;

/**
 * Class YamlLoader
 * @package Habanero\Framework\Routing
 */
class YamlLoader implements LoaderInterface
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var string
     */
    protected $modulePath;

    /**
     * YamlLoader constructor.
     */
    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * @inheritdoc
     */
    public function load($modulePath)
    {
        $this->modulePath = $modulePath;
        $routing = [];

        foreach ($this->getModulesDirectories() as $directory) {
            $routeFile = $modulePath.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$this->getRoutingConfigLocation();
            if (file_exists($routeFile)) {
                $routes = $this->parser->parse(file_get_contents($routeFile));

                foreach ($routes as $routeName => $route) {
                    $method = isset($route['method']) ? $route['method'] : 'GET';
                    $routing[] = new Route($routeName, $route['path'], $directory.':'.$route['action'], $method);
                }

            }
        }

        return $routing;
    }

    /**
     * Get routing config location
     * @return string
     */
    protected function getRoutingConfigLocation()
    {
        return 'config/routing.yaml';
    }

    /**
     * @return \CallbackFilterIterator|\DirectoryIterator
     */
    protected function getModulesDirectories()
    {
        $directories = new \DirectoryIterator($this->modulePath);
        $directories = new \CallbackFilterIterator($directories, function (\SplFileInfo $directory){
            return $directory->getBasename() != '.' && $directory->getBasename() != '..';
        });

        return $directories;
    }
}
