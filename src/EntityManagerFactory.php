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

        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver($entityManagerParameters->getEntityDirectories()));
        $config->setNamingStrategy($entityManagerParameters->getNamingStrategy());
        $config->setSchemaAssetsFilter($entityManagerParameters->getSchemaAssetsFilter());
        $config->setSchemaIgnoreClasses($entityManagerParameters->getSchemaIgnoreClasses());
        $config->setProxyDir($entityManagerParameters->getProxyDirectory());
        $config->setProxyNamespace($entityManagerParameters->getProxyNamespace());
        $config->setAutoGenerateProxyClasses($entityManagerParameters->getProxyAutogenerate());
        $config->setMetadataCache($entityManagerParameters->getMetadataCache());
        $config->setQueryCache($entityManagerParameters->getQueryCache());
        $config->setResultCache($entityManagerParameters->getResultCache());
        $config->setCustomDatetimeFunctions($entityManagerParameters->getCustomDatetimeFunctions());
        /** @psalm-suppress InvalidArgument */
        /** @phpstan-ignore-next-line */
        $config->setCustomNumericFunctions($entityManagerParameters->getCustomNumericFunctions());
        $config->setCustomStringFunctions($entityManagerParameters->getCustomStringFunctions());
        $config->setMiddlewares($entityManagerParameters->getMiddlewares());

        foreach ($entityManagerParameters->getConfigurators() as $configurator) {
            $configurator($config);
        }

        $connParams = (new DsnParser())->parse($entityManagerParameters->getDsn());
        $connection = DriverManager::getConnection($connParams, $config);

        $eventManager = new EventManager();
        foreach ($entityManagerParameters->getEventSubscribers() as $eventSubscriber) {
            $eventManager->addEventSubscriber($eventSubscriber);
        }

        return new EntityManager($connection, $config, $eventManager);
    }
}
