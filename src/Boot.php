<?php
namespace Habanero;

use Habanero\Exceptions\ActionNotFoundException;
use Habanero\Exceptions\InvalidHandlerException;
use Habanero\Exceptions\NoConfigException;
use Habanero\Framework\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Yaml\Parser;
use Habanero\Framework\Routing\YamlLoader;
use Habanero\Framework\Routing\Routing;
use Habanero\Framework\Routing\Route;
use FastRoute\Dispatcher;
use FastRoute;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class Boot
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $mainDirectory;

    /**
     * @var Route[]
     */
    protected $rawRouting;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var Controller
     */
    protected $controller;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Boot constructor.
     * @param string $mainDirectory
     */
    public function __construct($mainDirectory)
    {
        $this->handleRequest();
        $this->parser = new Parser();
        $this->mainDirectory = $mainDirectory;
    }

    public function boot()
    {
        $this->loadConfig();
        $this->loadRouting();

        $this->prepareDoctrine();
        $this->buildContainer();

        if (php_sapi_name() != "cli") {
            $this->route();
        }
    }

    /**
     * @param string $handler
     * @param array $vars
     * @return Response
     */
    protected function fireController($handler, $vars = [])
    {
        if (!preg_match('/\w+:\w+:\w+/', $handler)) {
            throw new InvalidHandlerException(sprintf("Invalid handler name: %s", $handler));
        }
        $handler = explode(':', $handler);

        $class = $handler[0]."\\Controller\\".$handler[1]."Controller";
        $method = $handler[2]."Action";

        $this->controller = new $class();
        if (!method_exists($this->controller, $method)) {
            throw new ActionNotFoundException(sprintf("Action '%s' not found"));
        }
        $this->controller->setContainer($this->container);
        $this->controller->setRequest($this->request);

        return call_user_func_array([$this->controller, $method], $vars);
    }

    protected function prepareDoctrine()
    {
        $paths = iterator_to_array($this->loadEntityPaths());

        $config = Setup::createAnnotationMetadataConfiguration(
            $paths,
            false,
            null,
            null,
            false
        );
        $this->entityManager = EntityManager::create($this->config['database'], $config);
    }

    protected function getModulesPaths()
    {
        $directories = new \DirectoryIterator($this->mainDirectory.DIRECTORY_SEPARATOR.$this->config['module']);
        $directories = new \CallbackFilterIterator($directories, function (\SplFileInfo $directory){
            return $directory->getBasename() != '.' && $directory->getBasename() != '..';
        });

        return $directories;
    }

    protected function loadEntityPaths()
    {
        foreach ($this->getModulesPaths() as $modulePath) {
            $path = $this->config['module'].DIRECTORY_SEPARATOR.$modulePath.DIRECTORY_SEPARATOR."Entity";
            if (file_exists($path)) {
                yield $path;
            }
        }
    }

    protected function buildContainer()
    {
        $this->container = new Container();

        $this->container['session'] = function ($c) {
            return new Session();
        };

        $this->container['entity_manager'] = function($c) {
            return $this->entityManager;
        };
    }

    protected function route()
    {
        $httpMethod = $this->request->getMethod();
        $uri = $this->request->getRequestUri();

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                echo 'blad 404';
                break;
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                echo 'zla metoda';
                break;
            case FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                $response = $this->fireController($handler, $vars);
                $response->prepare($this->request);
                $response->send();
                break;
        }
    }

    protected function handleRequest()
    {
        $this->request = Request::createFromGlobals();
        $this->baseUrl = $this->request->getBaseUrl();
    }

    protected function buildRouting()
    {
        $this->dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
            foreach ($this->rawRouting as $route) {
                $r->addRoute($route->getMethod(), $this->baseUrl.$route->getPath(), $route->getAction());
            }
        });
    }

    protected function loadRouting()
    {
        $loader = new YamlLoader();
        $routing = new Routing($loader, $this->mainDirectory.DIRECTORY_SEPARATOR.$this->config['module']);
        $routing->load();

        $this->rawRouting = $routing->getRouting();

        $this->buildRouting();
    }

    protected function loadConfig()
    {
        $configFile = $this->mainDirectory.DIRECTORY_SEPARATOR.'config.yaml';
        if (!is_readable($configFile)) {
            throw new NoConfigException(sprintf('No found config file at: %s', $configFile));
        }
        $this->config = $this->parser->parse(file_get_contents($configFile));
    }
}