<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use SensitiveParameter;
use stdClass;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerParameters;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class EntityManagerParametersTest extends TestCase
{
    public function testConstructor(): void
    {
        $name = $this->createMock(EntityManagerNameInterface::class);
        $namingStrategy = $this->createMock(NamingStrategy::class);
        $schemaAssetFilter = static fn(): bool => false;
        $metadataCache = $this->createMock(CacheItemPoolInterface::class);
        $queryCache = $this->createMock(CacheItemPoolInterface::class);
        $resultCache = $this->createMock(CacheItemPoolInterface::class);
        $customDatetimeFunction = $this->createMock(FunctionNode::class);
        $customNumericFunction = $this->createMock(FunctionNode::class);
        $customStringFunction = $this->createMock(FunctionNode::class);
        $configurator = static fn() => null;
        $logger = $this->createMock(LoggerInterface::class);

        $middlewares = [];
        $middlewares[] = $this->createMock(Middleware::class);
        $middlewares[] = $this->createMock(Middleware::class);

        $eventSubscribers = [];
        $eventSubscribers[] = $this->createMock(EventSubscriber::class);
        $eventSubscribers[] = $this->createMock(EventSubscriber::class);

        $entityManagerConfigurator = static fn(EntityManagerInterface $em) => null;

        $parameters = new EntityManagerParameters(
            name: $name,
            dsn: 'dsn',
            entityDirectories: ['/test-doctrine-entities'],
            namingStrategy: $namingStrategy,
            schemaAssetFilters: [$schemaAssetFilter],
            schemaIgnoreClasses: [stdClass::class],
            proxyDirectory: '/test-doctrine-proxies',
            proxyNamespace: 'TestDoctrineProxies',
            proxyAutogenerate: ProxyFactory::AUTOGENERATE_NEVER,
            metadataCache: $metadataCache,
            queryCache: $queryCache,
            resultCache: $resultCache,
            customDatetimeFunctions: ['test-datetime' => $customDatetimeFunction::class],
            customNumericFunctions: ['test-numeric' => $customNumericFunction::class],
            customStringFunctions: ['test-string' => $customStringFunction::class],
            middlewares: $middlewares,
            eventSubscribers: $eventSubscribers,
            configurators: [$configurator],
            types: [GuidType::class => GuidType::class],
            logger: $logger,
            entityManagerConfigurators: [$entityManagerConfigurator],
        );

        self::assertSame($name, $parameters->getName());
        self::assertSame('dsn', $parameters->getDsn());
        self::assertSame(['/test-doctrine-entities'], $parameters->getEntityDirectories());
        self::assertSame($namingStrategy, $parameters->getNamingStrategy());
        self::assertSame(false, ($parameters->getSchemaAssetsFilter())(null));
        self::assertSame([stdClass::class], $parameters->getSchemaIgnoreClasses());
        self::assertSame('/test-doctrine-proxies', $parameters->getProxyDirectory());
        self::assertSame('TestDoctrineProxies', $parameters->getProxyNamespace());
        self::assertSame(ProxyFactory::AUTOGENERATE_NEVER, $parameters->getProxyAutogenerate());
        self::assertSame($metadataCache, $parameters->getMetadataCache());
        self::assertSame($queryCache, $parameters->getQueryCache());
        self::assertSame($resultCache, $parameters->getResultCache());
        self::assertSame(['test-datetime' => $customDatetimeFunction::class], $parameters->getCustomDatetimeFunctions());
        self::assertSame(['test-numeric' => $customNumericFunction::class], $parameters->getCustomNumericFunctions());
        self::assertSame(['test-string' => $customStringFunction::class], $parameters->getCustomStringFunctions());
        $actualMiddlewares = $parameters->getMiddlewares();
        self::assertCount(3, $actualMiddlewares);
        $actualMiddlewareNames = \array_map(static fn(object $o) => $o::class, $actualMiddlewares);
        self::assertContains($middlewares[0]::class, $actualMiddlewareNames);
        self::assertContains($middlewares[1]::class, $actualMiddlewareNames);
        self::assertContains(\Doctrine\DBAL\Logging\Middleware::class, $actualMiddlewareNames);
        self::assertSame($eventSubscribers, $parameters->getEventSubscribers());
        self::assertSame([$configurator], $parameters->getConfigurators());
        self::assertSame([GuidType::class => GuidType::class], $parameters->getTypes());
        self::assertSame($logger, $parameters->getLogger());
        self::assertSame([$entityManagerConfigurator], $parameters->getEntityManagerConfigurators());
    }

    public function testDefaultParameters(): void
    {
        $name = $this->createMock(EntityManagerNameInterface::class);
        $parameters = new EntityManagerParameters(name: $name, dsn: 'secret');

        self::assertSame([], $parameters->getEntityDirectories());
        self::assertSame(UnderscoreNamingStrategy::class, $parameters->getNamingStrategy()::class);
        self::assertSame(true, ($parameters->getSchemaAssetsFilter())(null));
        self::assertSame([], $parameters->getSchemaIgnoreClasses());
        $defaultProxyDirectory = \sys_get_temp_dir() . '/doctrine-proxies';
        self::assertSame($defaultProxyDirectory, $parameters->getProxyDirectory());
        self::assertSame('DoctrineProxies', $parameters->getProxyNamespace());
        self::assertSame(ProxyFactory::AUTOGENERATE_ALWAYS, $parameters->getProxyAutogenerate());
        self::assertSame(ArrayAdapter::class, $parameters->getMetadataCache()::class);
        self::assertSame(ArrayAdapter::class, $parameters->getQueryCache()::class);
        self::assertSame(ArrayAdapter::class, $parameters->getResultCache()::class);
        self::assertSame([], $parameters->getCustomDatetimeFunctions());
        self::assertSame([], $parameters->getCustomNumericFunctions());
        self::assertSame([], $parameters->getCustomStringFunctions());
        self::assertSame([], $parameters->getMiddlewares());
        self::assertSame([], $parameters->getEventSubscribers());
        self::assertSame([], $parameters->getConfigurators());
        self::assertSame([], $parameters->getTypes());
        self::assertSame(null, $parameters->getLogger());
        self::assertSame([], $parameters->getEntityManagerConfigurators());
    }

    public function testSensitiveParameters(): void
    {
        $class = new ReflectionClass(EntityManagerParameters::class);
        $constructor = $class->getConstructor();

        $namedParameters = [];
        foreach ($constructor->getParameters() as $parameter) {
            $namedParameters[$parameter->name] = $parameter;
        }

        $parameterName = 'dsn';
        self::assertArrayHasKey($parameterName, $namedParameters);
        $annotations = $namedParameters[$parameterName]->getAttributes(SensitiveParameter::class);
        self::assertNotEmpty($annotations);
    }
}
