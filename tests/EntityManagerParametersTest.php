<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Repository\RepositoryFactory;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerParameters;

final class EntityManagerParametersTest extends TestCase
{
    public function testConstructor(): void
    {
        $name = $this->createStub(EntityManagerNameInterface::class);
        $dsn = 'pdo-sqlite:///:memory:';
        $types = ['guid' => GuidType::class];
        $middlewares = [$this->createStub(Middleware::class)];
        $entityDirectories = ['/src/Entity'];
        $namingStrategy = $this->createStub(NamingStrategy::class);
        $identityGenerationPreferences = [SQLitePlatform::class => ClassMetadata::GENERATOR_TYPE_AUTO];
        $entityListenerResolver = $this->createStub(EntityListenerResolver::class);
        $repositoryFactory = $this->createStub(RepositoryFactory::class);
        $schemaAssetFilters = [static fn(mixed $asset): bool => (bool) $asset];
        $schemaIgnoreClasses = [stdClass::class];
        $metadataCache = $this->createStub(CacheItemPoolInterface::class);
        $queryCache = $this->createStub(CacheItemPoolInterface::class);
        $resultCache = $this->createStub(CacheItemPoolInterface::class);
        $customDatetimeFunctions = ['CustomDatetimeFunction' => $this->createStub(FunctionNode::class)];
        $customNumericFunctions = ['CustomNumericFunction' => $this->createStub(FunctionNode::class)];
        $customStringFunctions = ['CustomStringFunction' => $this->createStub(FunctionNode::class)];
        $filters = ['softDelete' => SQLFilter::class];
        $enabledFilters = ['softDelete'];
        $proxyDir = '/tmp/doctrine/proxy';
        $proxyNamespace = 'App\\Doctrine\\Proxy';
        $autoGenerateProxyClasses = ProxyFactory::AUTOGENERATE_NEVER;
        $eventSubscribers = [$this->createStub(EventSubscriber::class)];
        $logger = $this->createStub(LoggerInterface::class);
        $configurators = [static fn(Configuration $configuration) => null];
        $entityManagerConfigurators = [static fn(EntityManagerInterface $entityManager) => null];

        $parameters = new EntityManagerParameters(
            name: $name,
            dsn: $dsn,
            types: $types,
            middlewares: $middlewares,
            entityDirectories: $entityDirectories,
            namingStrategy: $namingStrategy,
            identityGenerationPreferences: $identityGenerationPreferences,
            entityListenerResolver: $entityListenerResolver,
            repositoryFactory: $repositoryFactory,
            schemaAssetFilters: $schemaAssetFilters,
            schemaIgnoreClasses: $schemaIgnoreClasses,
            metadataCache: $metadataCache,
            queryCache: $queryCache,
            resultCache: $resultCache,
            customDatetimeFunctions: $customDatetimeFunctions,
            customNumericFunctions: $customNumericFunctions,
            customStringFunctions: $customStringFunctions,
            filters: $filters,
            enabledFilters: $enabledFilters,
            proxyDir: $proxyDir,
            proxyNamespace: $proxyNamespace,
            autoGenerateProxyClasses: $autoGenerateProxyClasses,
            eventSubscribers: $eventSubscribers,
            logger: $logger,
            configurators: $configurators,
            entityManagerConfigurators: $entityManagerConfigurators,
        );

        self::assertSame($name, $parameters->getName());
        self::assertSame($dsn, $parameters->getDsn());
        self::assertSame($types, $parameters->getTypes());
        self::assertCount(2, $parameters->getMiddlewares());
        self::assertSame($middlewares[0], $parameters->getMiddlewares()[0]);
        self::assertSame(\Doctrine\DBAL\Logging\Middleware::class, $parameters->getMiddlewares()[1]::class);
        self::assertSame($entityDirectories, $parameters->getEntityDirectories());
        self::assertSame($namingStrategy, $parameters->getNamingStrategy());
        self::assertSame($identityGenerationPreferences, $parameters->getIdentityGenerationPreferences());
        self::assertSame($entityListenerResolver, $parameters->getEntityListenerResolver());
        self::assertSame($repositoryFactory, $parameters->getRepositoryFactory());
        self::assertTrue($parameters->getSchemaAssetsFilter()(1));
        self::assertFalse($parameters->getSchemaAssetsFilter()(0));
        self::assertSame($schemaIgnoreClasses, $parameters->getSchemaIgnoreClasses());
        self::assertSame($metadataCache, $parameters->getMetadataCache());
        self::assertSame($queryCache, $parameters->getQueryCache());
        self::assertSame($resultCache, $parameters->getResultCache());
        self::assertSame($customDatetimeFunctions, $parameters->getCustomDatetimeFunctions());
        self::assertSame($customNumericFunctions, $parameters->getCustomNumericFunctions());
        self::assertSame($customStringFunctions, $parameters->getCustomStringFunctions());
        self::assertSame($filters, $parameters->getFilters());
        self::assertSame($enabledFilters, $parameters->getEnabledFilters());
        self::assertSame($proxyDir, $parameters->getProxyDir());
        self::assertSame($proxyNamespace, $parameters->getProxyNamespace());
        self::assertSame($autoGenerateProxyClasses, $parameters->getAutoGenerateProxyClasses());
        self::assertSame($eventSubscribers, $parameters->getEventSubscribers());
        self::assertSame($logger, $parameters->getLogger());
        self::assertSame($configurators, $parameters->getConfigurators());
        self::assertSame($entityManagerConfigurators, $parameters->getEntityManagerConfigurators());
    }
}
