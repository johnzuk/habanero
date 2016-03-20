<?php
namespace Habanero\Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Pimple\Container;
use Doctrine\ORM\EntityManager;

class Controller
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
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return FormFactoryInterface
     */
    public function getFormFactory()
    {
        return $this->formFactory;
    }

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function setFormFactory($formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @return FormBuilderInterface
     */
    public function createFormBuilder()
    {
        return $this->getFormFactory()->createBuilder();
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->container['session'];
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->container['entity_manager'];
    }

    /**
     * @return \Twig_Environment
     */
    public function getRenderView()
    {
        return $this->container['view'];
    }

    /**
     * @return \PHPMailer
     */
    public function getMailer()
    {
        return $this->container['mailer'];
    }

    /**
     * @param $file
     * @param array $vars
     * @return Response
     */
    public function render($file, $vars = [])
    {
        $response = new Response($this->getRenderView()->render($file, $vars));
        $response->prepare($this->request);
        return $response;
    }
}
