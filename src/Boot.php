<?php
namespace Habanero;

use Habanero\Exceptions\ActionNotFoundException;
use Habanero\Exceptions\InvalidHandlerException;
use Habanero\Exceptions\NoConfigException;
use Habanero\Framework\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pimple\Container;
use Symfony\Component\Yaml\Parser;
use Habanero\Framework\Routing\YamlLoader;
use Habanero\Framework\Routing\Routing;
use Habanero\Framework\Routing\Route;
use FastRoute\Dispatcher;
use FastRoute;
use Symfony\Component\HttpFoundation\Response;

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
        $this->route();
    }

    /**
     * @param $handler
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
        //set some properties
        return call_user_func_array([$this->controller, $method], $vars);
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