<?php
namespace Habanero\Framework\Routing;

/**
 * Class Route
 * @package Habanero\Framework\Routing
 */
class Route
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $method = 'GET';

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $action;

    /**
     * Route constructor.
     * @param string $name
     * @param string $method
     * @param string $path
     * @param string $action
     */
    public function __construct($name, $path, $action, $method = 'GET')
    {
        $this->name = $name;
        $this->method = $method;
        $this->path = $path;
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
}
