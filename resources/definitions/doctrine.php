<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
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
use function DI\string;

return [
    'doctrine.logger' => null,
    'doctrine.system_temporary_directory' => sys_get_temp_dir(),

    'doctrine.entity_manager_registry.logger' => get('doctrine.logger'),

    'doctrine.entity_manager_parameters.*.entity_directories' => [],
    'doctrine.entity_manager_parameters.*.proxy_directory' => string('{doctrine.system_temporary_directory}/doctrine-proxies'),
    'doctrine.entity_manager_parameters.*.proxy_namespace' => 'DoctrineProxies',
    'doctrine.entity_manager_parameters.*.proxy_autogenerate' => ProxyFactory::AUTOGENERATE_ALWAYS,
    'doctrine.entity_manager_parameters.*.default_cache' => get(CacheItemPoolInterface::class),
    'doctrine.entity_manager_parameters.*.naming_strategy' => create(UnderscoreNamingStrategy::class),
    'doctrine.entity_manager_parameters.*.logger' => get('doctrine.logger'),
    'doctrine.entity_manager_parameters.*.event_subscribers' => [],
    'doctrine.entity_manager_parameters.*.middlewares' => [],

    'doctrine.entity_manager_parameters.default.name' => EntityManagerName::Default,
    'doctrine.entity_manager_parameters.default.dsn' => env('DATABASE_DSN'),
    'doctrine.entity_manager_parameters.default.entity_directories' => get('doctrine.entity_manager_parameters.*.entity_directories'),
    'doctrine.entity_manager_parameters.default.proxy_directory' => get('doctrine.entity_manager_parameters.*.proxy_directory'),
    'doctrine.entity_manager_parameters.default.proxy_namespace' => get('doctrine.entity_manager_parameters.*.proxy_namespace'),
    'doctrine.entity_manager_parameters.default.proxy_autogenerate' => get('doctrine.entity_manager_parameters.*.proxy_autogenerate'),
    'doctrine.entity_manager_parameters.default.metadata_cache' => get('doctrine.entity_manager_parameters.default.default_cache'),
    'doctrine.entity_manager_parameters.default.query_cache' => get('doctrine.entity_manager_parameters.default.default_cache'),
    'doctrine.entity_manager_parameters.default.result_cache' => get('doctrine.entity_manager_parameters.default.default_cache'),
    'doctrine.entity_manager_parameters.default.default_cache' => get('doctrine.entity_manager_parameters.*.default_cache'),
    'doctrine.entity_manager_parameters.default.naming_strategy' => get('doctrine.entity_manager_parameters.*.naming_strategy'),
    'doctrine.entity_manager_parameters.default.logger' => get('doctrine.entity_manager_parameters.*.logger'),
    'doctrine.entity_manager_parameters.default.event_subscribers' => get('doctrine.entity_manager_parameters.*.event_subscribers'),
    'doctrine.entity_manager_parameters.default.middlewares' => get('doctrine.entity_manager_parameters.*.middlewares'),

    'doctrine.entity_manager_parameters_list' => [
        create(EntityManagerParameters::class)
            ->constructor(
                name: get('doctrine.entity_manager_parameters.default.name'),
                dsn: get('doctrine.entity_manager_parameters.default.dsn'),
                entityDirectories: get('doctrine.entity_manager_parameters.default.entity_directories'),
                proxyDirectory: get('doctrine.entity_manager_parameters.default.proxy_directory'),
                proxyNamespace: get('doctrine.entity_manager_parameters.default.proxy_namespace'),
                proxyAutogenerate: get('doctrine.entity_manager_parameters.default.proxy_autogenerate'),
                metadataCache: get('doctrine.entity_manager_parameters.default.metadata_cache'),
                queryCache: get('doctrine.entity_manager_parameters.default.query_cache'),
                resultCache: get('doctrine.entity_manager_parameters.default.result_cache'),
                namingStrategy: get('doctrine.entity_manager_parameters.default.naming_strategy'),
                logger: get('doctrine.entity_manager_parameters.default.logger'),
                eventSubscribers: get('doctrine.entity_manager_parameters.default.event_subscribers'),
                middlewares: get('doctrine.entity_manager_parameters.default.middlewares'),
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
