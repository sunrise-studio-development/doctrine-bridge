<?php

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatolii Nekhai <afenric@gmail.com>
 * @copyright Copyright (c) 2025, Anatolii Nekhai
 * @license https://github.com/sunrise-studio-development/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-studio-development/doctrine-bridge
 */

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;

final readonly class EntityManagerFactory implements EntityManagerFactoryInterface
{
    public function createEntityManagerFromParameters(
        EntityManagerParametersInterface $entityManagerParameters,
    ): EntityManagerInterface {
        foreach ($entityManagerParameters->getTypes() as $key => $typeClass) {
            $typeName = \is_int($key) ? $typeClass : $key;

            Type::hasType($typeName) or
            Type::addType($typeName, $typeClass);
        }

        $configuration = new Configuration();
        $configuration->setMiddlewares($entityManagerParameters->getMiddlewares());
        $configuration->setMetadataDriverImpl(new AttributeDriver($entityManagerParameters->getEntityDirectories()));
        $configuration->setNamingStrategy($entityManagerParameters->getNamingStrategy());
        $configuration->setIdentityGenerationPreferences($entityManagerParameters->getIdentityGenerationPreferences());
        $configuration->setEntityListenerResolver($entityManagerParameters->getEntityListenerResolver());
        $configuration->setRepositoryFactory($entityManagerParameters->getRepositoryFactory());
        $configuration->setSchemaAssetsFilter($entityManagerParameters->getSchemaAssetsFilter());
        $configuration->setSchemaIgnoreClasses($entityManagerParameters->getSchemaIgnoreClasses());
        $configuration->setMetadataCache($entityManagerParameters->getMetadataCache());
        $configuration->setQueryCache($entityManagerParameters->getQueryCache());
        $configuration->setResultCache($entityManagerParameters->getResultCache());
        $configuration->setCustomDatetimeFunctions($entityManagerParameters->getCustomDatetimeFunctions());
        /** @psalm-suppress InvalidArgument */
        $configuration->setCustomNumericFunctions($entityManagerParameters->getCustomNumericFunctions());
        $configuration->setCustomStringFunctions($entityManagerParameters->getCustomStringFunctions());

        foreach ($entityManagerParameters->getFilters() as $filterName => $filterClass) {
            $configuration->addFilter($filterName, $filterClass);
        }

        // https://github.com/doctrine/orm/pull/12005
        if (\PHP_VERSION_ID < 80400) {
            $configuration->setProxyDir($entityManagerParameters->getProxyDir());
            $configuration->setProxyNamespace($entityManagerParameters->getProxyNamespace());
            $configuration->setAutoGenerateProxyClasses($entityManagerParameters->getAutoGenerateProxyClasses());
        } else {
            $configuration->enableNativeLazyObjects(true);
        }

        foreach ($entityManagerParameters->getConfigurators() as $configurator) {
            $configurator($configuration);
        }

        $parsedDsn = (new DsnParser())->parse($entityManagerParameters->getDsn());
        $connection = DriverManager::getConnection($parsedDsn, $configuration);
        $entityManager = new EntityManager($connection, $configuration);

        foreach ($entityManagerParameters->getEnabledFilters() as $filterName) {
            $entityManager->getFilters()->enable($filterName);
        }

        foreach ($entityManagerParameters->getEventSubscribers() as $eventSubscriber) {
            $entityManager->getEventManager()->addEventSubscriber($eventSubscriber);
        }

        foreach ($entityManagerParameters->getEntityManagerConfigurators() as $configurator) {
            $configurator($entityManager);
        }

        return $entityManager;
    }
}
