<?php
namespace Habanero\Framework\Routing;

interface LoaderInterface
{
    /**
     * @param string $modulePath
     * @return Route[]
     */
    public function load($modulePath);
}