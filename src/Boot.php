<?php
namespace Habanero;

use Habanero\Exceptions\ActionNotFoundException;
use Habanero\Exceptions\InvalidHandlerException;
use Habanero\Exceptions\MethodNotAllowedException;
use Habanero\Exceptions\NotFoundException;
use Habanero\Framework\Config\Config;
use Habanero\Framework\Controller;
use Habanero\Framework\Routing\YamlLoader;
use Habanero\Framework\Routing\Routing;
use Habanero\Framework\Routing\Route;
use Habanero\Framework\Service\DoctrineService;
use Habanero\Framework\Service\MailService;
use Habanero\Framework\Service\TwigService;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use FastRoute\Dispatcher;
use FastRoute;
use Doctrine\ORM\EntityManager;
use Pimple\Container;

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
     * @var \PHPMailer
     */
    protected $mailer;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

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
        $this->config = new Config($mainDirectory, $this->request);

        $this->parser = new Parser();
    }

    public function boot()
    {
        $this->loadRouting();

        $this->buildServices();
        $this->validator = Validation::createValidator();
        $this->buildContainer();

        $this->prepareFormFactory();

        if (php_sapi_name() != "cli") {
            try {
                $response = $this->route();
            } catch (NotFoundException $e) {
                $response = new Response($this->viewRender->render('error404.html.twig', [
                    'message' => $e->getMessage()
                ]), 404);
            } catch (MethodNotAllowedException $e) {
                $this->viewRender->render('error405.html.twig');
                $response = new Response($this->viewRender->render('error405.html.twig', [
                    'message' => $e->getMessage()
                ]), 405);
            }

            $response->prepare($this->request);
            $response->send();
        }
    }

    protected function buildServices()
    {
        $this->entityManager = (new DoctrineService())->getService($this->config);
        $this->viewRender = (new TwigService())->getService($this->config);
        $this->mailer = (new MailService())->getService($this->config);
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
            throw new ActionNotFoundException(sprintf("Action '%s' not found", $method));
        }
        $this->controller->setContainer($this->container);
        $this->controller->setRequest($this->request);
        $this->controller->setFormFactory($this->formFactory);

        if (method_exists($this->controller, 'beforeRoute')) {
            call_user_func_array([$this->controller, 'beforeRoute'], [$method, $vars]);
        }

        $result = call_user_func_array([$this->controller, $method], $vars);

        if (method_exists($this->controller, 'afterRoute')) {
            call_user_func_array([$this->controller, 'afterRoute'], [$method, $vars]);
        }

        return $result;
    }

    public function prepareFormFactory()
    {
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($this->validator))
            ->getFormFactory();
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

        $this->container['mailer'] = function ($c) {
            return $this->mailer;
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

                return $this->fireController($handler, $vars);
                break;
            default:
                throw new NotFoundException(sprintf("Not found route: '%s'", $routeInfo[0]));
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
        $callback = function(FastRoute\RouteCollector $r) {
            foreach ($this->rawRouting as $route) {
                $r->addRoute($route->getMethod(), $this->baseUrl.$route->getPath(), $route->getAction());
            }
        };

        if ($this->config['app']['dev']) {
            $this->dispatcher = FastRoute\simpleDispatcher($callback);
        } else {
            $this->dispatcher = FastRoute\cachedDispatcher($callback, [
                'cacheFile' => $this->config->getRouteCachePatch().DIRECTORY_SEPARATOR.'route.cache',
                'cacheDisabled' => false,
            ]);
        }

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
