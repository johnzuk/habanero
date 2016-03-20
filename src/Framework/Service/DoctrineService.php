<?php
namespace Habanero\Framework\Service;

use Habanero\Framework\Config\Config;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class DoctrineService implements ServiceInterface
{
    /**
     * @param Config $config
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    public function getService(Config $config)
    {
        $paths = iterator_to_array($config->getEntityPaths());

        $configEm = Setup::createAnnotationMetadataConfiguration(
            $paths,
            false,
            null,
            null,
            false
        );

        return EntityManager::create($config['database'], $configEm);
    }
}