<?php declare(strict_types=1);

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2020, Anatoly Fenric
 * @license https://github.com/sunrise-php/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-php/doctrine-bridge
 */

namespace Sunrise\Bridge\Doctrine;

/**
 * Import classes
 */
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

/**
 * EntityManagerFactory
 */
final class EntityManagerFactory
{

    /**
     * Creates a new entity manager instance from the parameters
     *
     * @param Connection $connection
     * @param array $parameters
     *
     * @return EntityManagerInterface
     */
    public function createEntityManager(Connection $connection, array $parameters) : EntityManagerInterface
    {
        $configuration = new Configuration();

        if (isset($parameters['proxy_dir'])) {
            $configuration->setProxyDir($parameters['proxy_dir']);
        }

        if (isset($parameters['proxy_auto_generate'])) {
            $configuration->setAutoGenerateProxyClasses($parameters['proxy_auto_generate']);
        } elseif (isset($parameters['auto_generate_proxy_classes'])) {
            $configuration->setAutoGenerateProxyClasses($parameters['auto_generate_proxy_classes']);
        }

        if (isset($parameters['proxy_namespace'])) {
            $configuration->setProxyNamespace($parameters['proxy_namespace']);
        }

        if (isset($parameters['metadata_sources'])) {
            $configuration->setMetadataDriverImpl(
                $configuration->newDefaultAnnotationDriver($parameters['metadata_sources'], false)
            );
        } elseif (isset($parameters['metadata_driver'])) {
            $configuration->setMetadataDriverImpl($parameters['metadata_driver']);
        }

        if (isset($parameters['entity_namespaces'])) {
            $configuration->setEntityNamespaces($parameters['entity_namespaces']);
        }

        if (isset($parameters['query_cache'])) {
            Helper::setCacheToConfiguration($configuration, $parameters['query_cache'], 'QueryCache');
        }

        if (isset($parameters['hydration_cache'])) {
            Helper::setCacheToConfiguration($configuration, $parameters['hydration_cache'], 'HydrationCache');
        }

        if (isset($parameters['metadata_cache'])) {
            Helper::setCacheToConfiguration($configuration, $parameters['metadata_cache'], 'MetadataCache');
        }

        if (isset($parameters['custom_string_functions'])) {
            $configuration->setCustomStringFunctions($parameters['custom_string_functions']);
        }

        if (isset($parameters['custom_numeric_functions'])) {
            $configuration->setCustomNumericFunctions($parameters['custom_numeric_functions']);
        }

        if (isset($parameters['custom_datetime_functions'])) {
            $configuration->setCustomDatetimeFunctions($parameters['custom_datetime_functions']);
        }

        if (isset($parameters['custom_hydration_modes'])) {
            $configuration->setCustomHydrationModes($parameters['custom_hydration_modes']);
        }

        if (isset($parameters['class_metadata_factory_name'])) {
            $configuration->setClassMetadataFactoryName($parameters['class_metadata_factory_name']);
        }

        if (isset($parameters['default_repository_class_name'])) {
            $configuration->setDefaultRepositoryClassName($parameters['default_repository_class_name']);
        }

        if (isset($parameters['naming_strategy'])) {
            $configuration->setNamingStrategy($parameters['naming_strategy']);
        }

        if (isset($parameters['quote_strategy'])) {
            $configuration->setQuoteStrategy($parameters['quote_strategy']);
        }

        if (isset($parameters['entity_listener_resolver'])) {
            $configuration->setEntityListenerResolver($parameters['entity_listener_resolver']);
        }

        if (isset($parameters['repository_factory'])) {
            $configuration->setRepositoryFactory($parameters['repository_factory']);
        }

        if (isset($parameters['second_level_cache_enabled'])) {
            $configuration->setSecondLevelCacheEnabled($parameters['second_level_cache_enabled']);
        }

        if (isset($parameters['second_level_cache_configuration'])) {
            $configuration->setSecondLevelCacheConfiguration($parameters['second_level_cache_configuration']);
        }

        if (isset($parameters['default_query_hints'])) {
            $configuration->setDefaultQueryHints($parameters['default_query_hints']);
        }

        return EntityManager::create(
            $connection,
            $configuration,
            $connection->getEventManager()
        );
    }
}
