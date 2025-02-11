<?php

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Nekhay <afenric@gmail.com>
 * @copyright Copyright (c) 2025, Anatoly Nekhay
 * @license https://github.com/sunrise-studio-development/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-studio-development/doctrine-bridge
 */

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;

final readonly class EntityManagerFactory implements EntityManagerFactoryInterface
{
    public function createEntityManagerFromParameters(
        EntityManagerParametersInterface $entityManagerParameters,
    ): EntityManagerInterface {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver($entityManagerParameters->getEntityDirectories()));
        $config->setProxyDir($entityManagerParameters->getProxyDirectory());
        $config->setProxyNamespace($entityManagerParameters->getProxyNamespace());
        $config->setAutoGenerateProxyClasses($entityManagerParameters->getProxyAutogenerate());
        $config->setMetadataCache($entityManagerParameters->getMetadataCache());
        $config->setQueryCache($entityManagerParameters->getQueryCache());
        $config->setResultCache($entityManagerParameters->getResultCache());
        $config->setNamingStrategy($entityManagerParameters->getNamingStrategy());

        $params = (new DsnParser())->parse($entityManagerParameters->getDsn());
        $connection = DriverManager::getConnection($params, $config);

        return new EntityManager($connection, $config);
    }
}
