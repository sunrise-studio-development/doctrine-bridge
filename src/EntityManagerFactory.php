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
use Doctrine\ORM\Mapping\Driver\AttributeDriver;

/**
 * Import constants
 */
use const PHP_MAJOR_VERSION;

/**
 * EntityManagerFactory
 */
final class EntityManagerFactory
{

    /**
     * @var string
     *
     * @link https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/annotations-reference.html
     */
    public const METADATA_ANNOTATION_DRIVER_NAME = 'annotations';

    /**
     * @var string
     *
     * @link https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/attributes-reference.html
     */
    public const METADATA_ATTRIBUTE_DRIVER_NAME = 'attributes';

    /**
     * @var string
     */
    public const DEFAULT_METADATA_DRIVER_NAME = PHP_MAJOR_VERSION < 8 ?
                                                self::METADATA_ANNOTATION_DRIVER_NAME :
                                                self::METADATA_ATTRIBUTE_DRIVER_NAME;

    /**
     * @var array<string, scalar>
     */
    private const DEFAULT_PARAMS = [
        'metadata_driver' => self::DEFAULT_METADATA_DRIVER_NAME,
        'proxy_auto_generate' => true,
        'proxy_namespace' => 'DoctrineProxies',
    ];

    /**
     * @var array<string, string>
     */
    private const CONFIG_SETTER_MAP = [
        'auto_generate_proxy_classes' => 'setAutoGenerateProxyClasses',
        'class_metadata_factory_name' => 'setClassMetadataFactoryName',
        'custom_datetime_functions' => 'setCustomDatetimeFunctions',
        'custom_hydration_modes' => 'setCustomHydrationModes',
        'custom_numeric_functions' => 'setCustomNumericFunctions',
        'custom_string_functions' => 'setCustomStringFunctions',
        'default_query_hints' => 'setDefaultQueryHints',
        'default_repository_class_name' => 'setDefaultRepositoryClassName',
        'entity_listener_resolver' => 'setEntityListenerResolver',
        'entity_namespaces' => 'setEntityNamespaces',
        'metadata_driver' => 'setMetadataDriverImpl',
        'naming_strategy' => 'setNamingStrategy',
        'proxy_auto_generate' => 'setAutoGenerateProxyClasses', // alias to auto_generate_proxy_classes
        'proxy_dir' => 'setProxyDir',
        'proxy_namespace' => 'setProxyNamespace',
        'quote_strategy' => 'setQuoteStrategy',
        'repository_factory' => 'setRepositoryFactory',
        'second_level_cache_configuration' => 'setSecondLevelCacheConfiguration',
        'second_level_cache_enabled' => 'setSecondLevelCacheEnabled',
    ];

    /**
     * @var array<string, string>
     */
    private const CACHE_TYPE_MAP = [
        'hydration_cache' => 'HydrationCache',
        'metadata_cache' => 'MetadataCache',
        'query_cache' => 'QueryCache',
    ];

    /**
     * Creates a new entity manager instance from the parameters
     *
     * @param Connection $connection
     * @param array<string, mixed> $parameters
     *
     * @return EntityManagerInterface
     */
    public function createEntityManager(Connection $connection, array $parameters) : EntityManagerInterface
    {
        $parameters += self::DEFAULT_PARAMS;

        $configuration = new Configuration();

        if (isset($parameters['entity_locations'], $parameters['metadata_driver'])) {
            if (self::METADATA_ANNOTATION_DRIVER_NAME === $parameters['metadata_driver']) {
                $parameters['metadata_driver'] = $configuration->newDefaultAnnotationDriver(
                    (array) $parameters['entity_locations'],
                    false
                );
            }

            if (self::METADATA_ATTRIBUTE_DRIVER_NAME === $parameters['metadata_driver']) {
                $parameters['metadata_driver'] = new AttributeDriver(
                    (array) $parameters['entity_locations']
                );
            }
        }

        foreach (self::CONFIG_SETTER_MAP as $parameter => $setter) {
            if (isset($parameters[$parameter])) {
                $configuration->{$setter}($parameters[$parameter]);
            }
        }

        foreach (self::CACHE_TYPE_MAP as $parameter => $type) {
            if (isset($parameters[$parameter])) {
                Helper::setCacheToConfiguration($configuration, $parameters[$parameter], $type);
            }
        }

        return EntityManager::create(
            $connection,
            $configuration,
            $connection->getEventManager()
        );
    }
}
