<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Type;
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
use Sunrise\Bridge\Doctrine\EntityManagerFactory;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerParameters;

final class EntityManagerFactoryTest extends TestCase
{
    public function testCreatesEntityManagerFromParameters(): void
    {
        $driver = $this->createStub(Driver::class);
        $dsn = '?driverClass=' . $driver::class;
        $type = $this->createStub(Type::class);
        $types = [$type::class];
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
        $customDatetimeFunctions = ['CustomDatetimeFunction' => $this->createStub(FunctionNode::class)::class];
        $customNumericFunctions = ['CustomNumericFunction' => $this->createStub(FunctionNode::class)::class];
        $customStringFunctions = ['CustomStringFunction' => $this->createStub(FunctionNode::class)::class];
        $filters = ['softDelete' => $this->createStub(SQLFilter::class)::class];
        $enabledFilters = ['softDelete'];
        $proxyDir = '/tmp/doctrine-proxy';
        $proxyNamespace = 'DoctrineProxy';
        $autoGenerateProxyClasses = ProxyFactory::AUTOGENERATE_NEVER;
        $eventSubscribers = [$this->createConfiguredStub(EventSubscriber::class, ['getSubscribedEvents' => ['foo']])];
        $logger = $this->createStub(LoggerInterface::class);
        $configurators = [static function (Configuration $configuration) {
            $configuration->setAutoCommit(false);
        }];
        $entityManagerConfigurators = [static function (EntityManagerInterface $entityManager) {
            $entityManager->getConfiguration()->setEagerFetchBatchSize(1_000);
        }];

        $entityManagerParameters = new EntityManagerParameters(
            name: $this->createStub(EntityManagerNameInterface::class),
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

        $entityManager = (new EntityManagerFactory())->createEntityManagerFromParameters($entityManagerParameters);

        self::assertSame($type::class, Type::getType($type::class)::class);
        self::assertCount(2, $entityManager->getConfiguration()->getMiddlewares());
        self::assertSame($middlewares[0], $entityManager->getConfiguration()->getMiddlewares()[0]);
        self::assertSame(\Doctrine\DBAL\Logging\Middleware::class, $entityManager->getConfiguration()->getMiddlewares()[1]::class);
        self::assertSame($entityDirectories, $entityManager->getConfiguration()->getMetadataDriverImpl()->getPaths());
        self::assertSame($namingStrategy, $entityManager->getConfiguration()->getNamingStrategy());
        self::assertSame($identityGenerationPreferences, $entityManager->getConfiguration()->getIdentityGenerationPreferences());
        self::assertSame($entityListenerResolver, $entityManager->getConfiguration()->getEntityListenerResolver());
        self::assertSame($repositoryFactory, $entityManager->getConfiguration()->getRepositoryFactory());
        self::assertTrue($entityManager->getConfiguration()->getSchemaAssetsFilter()(1));
        self::assertFalse($entityManager->getConfiguration()->getSchemaAssetsFilter()(0));
        self::assertSame($schemaIgnoreClasses, $entityManager->getConfiguration()->getSchemaIgnoreClasses());
        self::assertSame($metadataCache, $entityManager->getConfiguration()->getMetadataCache());
        self::assertSame($queryCache, $entityManager->getConfiguration()->getQueryCache());
        self::assertSame($resultCache, $entityManager->getConfiguration()->getResultCache());
        self::assertSame($customDatetimeFunctions['CustomDatetimeFunction'], $entityManager->getConfiguration()->getCustomDatetimeFunction('CustomDatetimeFunction'));
        self::assertSame($customNumericFunctions['CustomNumericFunction'], $entityManager->getConfiguration()->getCustomNumericFunction('CustomNumericFunction'));
        self::assertSame($customStringFunctions['CustomStringFunction'], $entityManager->getConfiguration()->getCustomStringFunction('CustomStringFunction'));
        self::assertSame($filters['softDelete'], $entityManager->getConfiguration()->getFilterClassName('softDelete'));
        self::assertTrue($entityManager->getFilters()->isEnabled('softDelete'));

        if (\PHP_VERSION_ID < 80_400) {
            self::assertSame($proxyDir, $entityManager->getConfiguration()->getProxyDir());
            self::assertSame($proxyNamespace, $entityManager->getConfiguration()->getProxyNamespace());
            self::assertSame($autoGenerateProxyClasses, $entityManager->getConfiguration()->getAutoGenerateProxyClasses());
        }

        self::assertEquals(\array_values($eventSubscribers), \array_values($entityManager->getEventManager()->getListeners('foo')));

        self::assertSame(false, $entityManager->getConfiguration()->getAutoCommit());
        self::assertSame(1_000, $entityManager->getConfiguration()->getEagerFetchBatchSize());
    }
}
