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

use Doctrine\Common\EventManager;
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
        foreach ($entityManagerParameters->getTypes() as $typeName => $typeClass) {
            Type::hasType($typeName) or Type::addType($typeName, $typeClass);
        }

        $configuration = new Configuration();
        $configuration->setMetadataDriverImpl(new AttributeDriver($entityManagerParameters->getEntityDirectories()));
        $configuration->setNamingStrategy($entityManagerParameters->getNamingStrategy());
        $configuration->setSchemaAssetsFilter($entityManagerParameters->getSchemaAssetsFilter());
        $configuration->setSchemaIgnoreClasses($entityManagerParameters->getSchemaIgnoreClasses());
        $configuration->setProxyDir($entityManagerParameters->getProxyDirectory());
        $configuration->setProxyNamespace($entityManagerParameters->getProxyNamespace());
        $configuration->setAutoGenerateProxyClasses($entityManagerParameters->getProxyAutogenerate());
        $configuration->setMetadataCache($entityManagerParameters->getMetadataCache());
        $configuration->setQueryCache($entityManagerParameters->getQueryCache());
        $configuration->setResultCache($entityManagerParameters->getResultCache());
        $configuration->setCustomDatetimeFunctions($entityManagerParameters->getCustomDatetimeFunctions());
        /** @psalm-suppress InvalidArgument */
        /** @phpstan-ignore-next-line */
        $configuration->setCustomNumericFunctions($entityManagerParameters->getCustomNumericFunctions());
        $configuration->setCustomStringFunctions($entityManagerParameters->getCustomStringFunctions());
        $configuration->setMiddlewares($entityManagerParameters->getMiddlewares());

        foreach ($entityManagerParameters->getConfigurators() as $configurator) {
            $configurator($configuration);
        }

        $parsedDsn = (new DsnParser())->parse($entityManagerParameters->getDsn());
        $connection = DriverManager::getConnection($parsedDsn, $configuration);

        $eventManager = new EventManager();
        foreach ($entityManagerParameters->getEventSubscribers() as $eventSubscriber) {
            $eventManager->addEventSubscriber($eventSubscriber);
        }

        $entityManager = new EntityManager($connection, $configuration, $eventManager);
        foreach ($entityManagerParameters->getEntityManagerConfigurators() as $configurator) {
            $configurator($entityManager);
        }

        return $entityManager;
    }
}
