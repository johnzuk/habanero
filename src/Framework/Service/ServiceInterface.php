<?php
namespace Habanero\Framework\Service;

use Habanero\Framework\Config\Config;

interface ServiceInterface
{
    public function getService(Config $config);
}