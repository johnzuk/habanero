<?php
namespace Habanero;

use Symfony\Component\HttpFoundation\Request;
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
}