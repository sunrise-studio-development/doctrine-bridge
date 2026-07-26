<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Psr\Cache\CacheItemPoolInterface;
use Sunrise\Bridge\Doctrine\Dictionary\EntityManagerName;
use Sunrise\Bridge\Doctrine\EntityManagerFactory;
use Sunrise\Bridge\Doctrine\EntityManagerFactoryInterface;
use Sunrise\Bridge\Doctrine\EntityManagerParameters;
use Sunrise\Bridge\Doctrine\EntityManagerRegistry;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;

use function DI\create;
use function DI\env;
use function DI\get;

return [
    'doctrine.logger' => null,
    'doctrine.entity_manager_registry.logger' => get('doctrine.logger'),

    'doctrine.entity_manager_parameters.*.types' => [],
    'doctrine.entity_manager_parameters.*.middlewares' => [],
    'doctrine.entity_manager_parameters.*.entity_directories' => [],
    'doctrine.entity_manager_parameters.*.naming_strategy' => create(UnderscoreNamingStrategy::class),
    'doctrine.entity_manager_parameters.*.identity_generation_preferences' => [],
    'doctrine.entity_manager_parameters.*.entity_listener_resolver' => create(DefaultEntityListenerResolver::class),
    'doctrine.entity_manager_parameters.*.repository_factory' => create(DefaultRepositoryFactory::class),
    'doctrine.entity_manager_parameters.*.schema_asset_filters' => [],
    'doctrine.entity_manager_parameters.*.schema_ignore_classes' => [],
    'doctrine.entity_manager_parameters.*.default_cache' => get(CacheItemPoolInterface::class),
    'doctrine.entity_manager_parameters.*.custom_datetime_functions' => [],
    'doctrine.entity_manager_parameters.*.custom_numeric_functions' => [],
    'doctrine.entity_manager_parameters.*.custom_string_functions' => [],
    'doctrine.entity_manager_parameters.*.filters' => [],
    'doctrine.entity_manager_parameters.*.enabled_filters' => [],
    'doctrine.entity_manager_parameters.*.proxy_dir' => sys_get_temp_dir() . '/doctrine-proxies',
    'doctrine.entity_manager_parameters.*.proxy_namespace' => 'DoctrineProxies',
    'doctrine.entity_manager_parameters.*.auto_generate_proxy_classes' => ProxyFactory::AUTOGENERATE_ALWAYS,
    'doctrine.entity_manager_parameters.*.event_subscribers' => [],
    'doctrine.entity_manager_parameters.*.logger' => get('doctrine.logger'),
    'doctrine.entity_manager_parameters.*.configurators' => [],
    'doctrine.entity_manager_parameters.*.entity_manager_configurators' => [],

    'doctrine.entity_manager_parameters.default.name' => EntityManagerName::Default,
    'doctrine.entity_manager_parameters.default.dsn' => env('DATABASE_DSN'),
    'doctrine.entity_manager_parameters.default.types' => get('doctrine.entity_manager_parameters.*.types'),
    'doctrine.entity_manager_parameters.default.middlewares' => get('doctrine.entity_manager_parameters.*.middlewares'),
    'doctrine.entity_manager_parameters.default.entity_directories' => get('doctrine.entity_manager_parameters.*.entity_directories'),
    'doctrine.entity_manager_parameters.default.naming_strategy' => get('doctrine.entity_manager_parameters.*.naming_strategy'),
    'doctrine.entity_manager_parameters.default.identity_generation_preferences' => get('doctrine.entity_manager_parameters.*.identity_generation_preferences'),
    'doctrine.entity_manager_parameters.default.entity_listener_resolver' => get('doctrine.entity_manager_parameters.*.entity_listener_resolver'),
    'doctrine.entity_manager_parameters.default.repository_factory' => get('doctrine.entity_manager_parameters.*.repository_factory'),
    'doctrine.entity_manager_parameters.default.schema_asset_filters' => get('doctrine.entity_manager_parameters.*.schema_asset_filters'),
    'doctrine.entity_manager_parameters.default.schema_ignore_classes' => get('doctrine.entity_manager_parameters.*.schema_ignore_classes'),
    'doctrine.entity_manager_parameters.default.default_cache' => get('doctrine.entity_manager_parameters.*.default_cache'),
    'doctrine.entity_manager_parameters.default.metadata_cache' => get('doctrine.entity_manager_parameters.default.default_cache'),
    'doctrine.entity_manager_parameters.default.query_cache' => get('doctrine.entity_manager_parameters.default.default_cache'),
    'doctrine.entity_manager_parameters.default.result_cache' => get('doctrine.entity_manager_parameters.default.default_cache'),
    'doctrine.entity_manager_parameters.default.custom_datetime_functions' => get('doctrine.entity_manager_parameters.*.custom_datetime_functions'),
    'doctrine.entity_manager_parameters.default.custom_numeric_functions' => get('doctrine.entity_manager_parameters.*.custom_numeric_functions'),
    'doctrine.entity_manager_parameters.default.custom_string_functions' => get('doctrine.entity_manager_parameters.*.custom_string_functions'),
    'doctrine.entity_manager_parameters.default.filters' => get('doctrine.entity_manager_parameters.*.filters'),
    'doctrine.entity_manager_parameters.default.enabled_filters' => get('doctrine.entity_manager_parameters.*.enabled_filters'),
    'doctrine.entity_manager_parameters.default.proxy_dir' => get('doctrine.entity_manager_parameters.*.proxy_dir'),
    'doctrine.entity_manager_parameters.default.proxy_namespace' => get('doctrine.entity_manager_parameters.*.proxy_namespace'),
    'doctrine.entity_manager_parameters.default.auto_generate_proxy_classes' => get('doctrine.entity_manager_parameters.*.auto_generate_proxy_classes'),
    'doctrine.entity_manager_parameters.default.event_subscribers' => get('doctrine.entity_manager_parameters.*.event_subscribers'),
    'doctrine.entity_manager_parameters.default.logger' => get('doctrine.entity_manager_parameters.*.logger'),
    'doctrine.entity_manager_parameters.default.configurators' => get('doctrine.entity_manager_parameters.*.configurators'),
    'doctrine.entity_manager_parameters.default.entity_manager_configurators' => get('doctrine.entity_manager_parameters.*.entity_manager_configurators'),

    'doctrine.entity_manager_parameters_list' => [
        create(EntityManagerParameters::class)
            ->constructor(
                name: get('doctrine.entity_manager_parameters.default.name'),
                dsn: get('doctrine.entity_manager_parameters.default.dsn'),
                types: get('doctrine.entity_manager_parameters.default.types'),
                middlewares: get('doctrine.entity_manager_parameters.default.middlewares'),
                entityDirectories: get('doctrine.entity_manager_parameters.default.entity_directories'),
                namingStrategy: get('doctrine.entity_manager_parameters.default.naming_strategy'),
                identityGenerationPreferences: get('doctrine.entity_manager_parameters.default.identity_generation_preferences'),
                entityListenerResolver: get('doctrine.entity_manager_parameters.default.entity_listener_resolver'),
                repositoryFactory: get('doctrine.entity_manager_parameters.default.repository_factory'),
                schemaAssetFilters: get('doctrine.entity_manager_parameters.default.schema_asset_filters'),
                schemaIgnoreClasses: get('doctrine.entity_manager_parameters.default.schema_ignore_classes'),
                metadataCache: get('doctrine.entity_manager_parameters.default.metadata_cache'),
                queryCache: get('doctrine.entity_manager_parameters.default.query_cache'),
                resultCache: get('doctrine.entity_manager_parameters.default.result_cache'),
                customDatetimeFunctions: get('doctrine.entity_manager_parameters.default.custom_datetime_functions'),
                customNumericFunctions: get('doctrine.entity_manager_parameters.default.custom_numeric_functions'),
                customStringFunctions: get('doctrine.entity_manager_parameters.default.custom_string_functions'),
                filters: get('doctrine.entity_manager_parameters.default.filters'),
                enabledFilters: get('doctrine.entity_manager_parameters.default.enabled_filters'),
                proxyDir: get('doctrine.entity_manager_parameters.default.proxy_dir'),
                proxyNamespace: get('doctrine.entity_manager_parameters.default.proxy_namespace'),
                autoGenerateProxyClasses: get('doctrine.entity_manager_parameters.default.auto_generate_proxy_classes'),
                eventSubscribers: get('doctrine.entity_manager_parameters.default.event_subscribers'),
                logger: get('doctrine.entity_manager_parameters.default.logger'),
                configurators: get('doctrine.entity_manager_parameters.default.configurators'),
                entityManagerConfigurators: get('doctrine.entity_manager_parameters.default.entity_manager_configurators'),
            )
    ],

    EntityManagerFactoryInterface::class => create(EntityManagerFactory::class),

    EntityManagerRegistryInterface::class => create(EntityManagerRegistry::class)
        ->constructor(
            entityManagerFactory: get(EntityManagerFactoryInterface::class),
            entityManagerParametersList: get('doctrine.entity_manager_parameters_list'),
            defaultEntityManagerName: get('doctrine.entity_manager_parameters.default.name'),
            logger: get('doctrine.entity_manager_registry.logger'),
        ),
];
