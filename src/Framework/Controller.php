<?php
namespace Habanero\Framework;

use Symfony\Component\HttpFoundation\Request;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Session\Session;
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
}
