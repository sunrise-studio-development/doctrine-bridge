<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Sunrise\Bridge\Doctrine\Dictionary\EntityManagerName;
use Sunrise\Bridge\Doctrine\EntityManagerFactory;
use Sunrise\Bridge\Doctrine\EntityManagerFactoryInterface;
use Sunrise\Bridge\Doctrine\EntityManagerParameters;
use Sunrise\Bridge\Doctrine\EntityManagerRegistry;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function DI\create;
use function DI\env;
use function DI\get;

return [
    'doctrine.logger' => null,

    'doctrine.entity_manager_parameters.*.entity_directories' => [],
    'doctrine.entity_manager_parameters.*.proxy_directory' => sys_get_temp_dir() . '/doctrine/proxies',
    'doctrine.entity_manager_parameters.*.proxy_namespace' => 'DoctrineProxies',
    'doctrine.entity_manager_parameters.*.proxy_autogenerate' => ProxyFactory::AUTOGENERATE_ALWAYS,
    'doctrine.entity_manager_parameters.*.default_cache' => create(ArrayAdapter::class),
    'doctrine.entity_manager_parameters.*.naming_strategy' => create(UnderscoreNamingStrategy::class),

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
            )
    ],

    EntityManagerFactoryInterface::class => create(EntityManagerFactory::class),

    EntityManagerRegistryInterface::class => create(EntityManagerRegistry::class)
        ->constructor(
            entityManagerFactory: get(EntityManagerFactoryInterface::class),
            entityManagerParametersList: get('doctrine.entity_manager_parameters_list'),
            defaultEntityManagerName: get('doctrine.entity_manager_parameters.default.name'),
            logger: get('doctrine.logger'),
        ),
];
