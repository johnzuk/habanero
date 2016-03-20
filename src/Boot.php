<?php
namespace Habanero;

use Habanero\Exceptions\ActionNotFoundException;
use Habanero\Exceptions\InvalidHandlerException;
use Habanero\Exceptions\MethodNotAllowedException;
use Habanero\Exceptions\NoConfigException;
use Habanero\Exceptions\NotFoundException;
use Habanero\Framework\Config\Config;
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
     * @var Config
     */
    protected $config;

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
     * @var \Twig_Environment
     */
    protected $viewRender;

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
        $this->config = new Config($mainDirectory);

        $this->parser = new Parser();
    }

    public function boot()
    {
        $this->loadRouting();

        $this->prepareDoctrine();
        $this->buildViewRender();
        $this->buildContainer();

        if (php_sapi_name() != "cli") {
            try {
                $this->route();
            } catch (NotFoundException $e) {
                $response = new Response($this->viewRender->render('error404.html.twig', [
                    'message' => $e->getMessage()
                ]), 404);
                $response->prepare($this->request);
                $response->send();
            } catch (MethodNotAllowedException $e) {
                $this->viewRender->render('error405.html.twig');
                $response = new Response($this->viewRender->render('error405.html.twig', [
                    'message' => $e->getMessage()
                ]), 405);
                $response->prepare($this->request);
                $response->send();
            }

        }
    }

    protected function buildViewRender()
    {
        $viewLoader = new \Twig_Loader_Filesystem([
            $this->config->getAppPath()
        ]);
        $this->viewRender = new \Twig_Environment($viewLoader, array(
            'debug' => true,
            //'cache' => $this->config->getViewCachePath(),
            'cache' => false
        ));
        $this->viewRender->addExtension(new \Twig_Extension_Debug());
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
        $paths = iterator_to_array($this->config->getEntityPaths());

        $config = Setup::createAnnotationMetadataConfiguration(
            $paths,
            false,
            null,
            null,
            false
        );
        $this->entityManager = EntityManager::create($this->config['database'], $config);
    }

    protected function buildContainer()
    {
        $this->container = new Container();

        $this->container['session'] = function ($c) {
            return new Session();
        };

        $this->container['entity_manager'] = function ($c) {
            return $this->entityManager;
        };

        $this->container['view'] = function ($c) {
            return $this->viewRender;
        };
    }

    protected function route()
    {
        $httpMethod = $this->request->getMethod();
        $uri = $this->request->getRequestUri();

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                throw new NotFoundException(sprintf("Not found route: '%s'", $routeInfo[0]));
                break;
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                throw new MethodNotAllowedException(sprintf("This method is not allowed. Allowed method: %s", $allowedMethods[0]));
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
        $routing = new Routing($loader, $this->config->getModuleDirPatch());
        $routing->load();

        $this->rawRouting = $routing->getRouting();

        $this->buildRouting();
    }

}
