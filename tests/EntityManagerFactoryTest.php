<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use stdClass;
use Sunrise\Bridge\Doctrine\EntityManagerFactory;
use Sunrise\Bridge\Doctrine\EntityManagerParametersInterface;

use function array_values;

final class EntityManagerFactoryTest extends TestCase
{
    public function testCreatesEntityManagerFromParameters(): void
    {
        $connectionDriver = $this->createMock(Driver::class);
        $namingStrategy = $this->createMock(NamingStrategy::class);
        $metadataCache = $this->createMock(CacheItemPoolInterface::class);
        $queryCache = $this->createMock(CacheItemPoolInterface::class);
        $resultCache = $this->createMock(CacheItemPoolInterface::class);

        $middlewares = [];
        $middlewares[0] = $this->createMock(Driver\Middleware::class);
        $middlewares[0]->expects($this->any())->method('wrap')->with($connectionDriver)->willReturn($connectionDriver);
        $middlewares[1] = $this->createMock(Driver\Middleware::class);
        $middlewares[1]->expects($this->any())->method('wrap')->with($connectionDriver)->willReturn($connectionDriver);

        $eventSubscribers = [];
        $eventSubscribers[0] = $this->createMock(EventSubscriber::class);
        $eventSubscribers[0]->expects($this->any())->method('getSubscribedEvents')->willReturn(['foo']);
        $eventSubscribers[1] = $this->createMock(EventSubscriber::class);
        $eventSubscribers[1]->expects($this->any())->method('getSubscribedEvents')->willReturn(['foo']);
        $eventSubscribers[2] = $this->createMock(EventSubscriber::class);
        $eventSubscribers[2]->expects($this->any())->method('getSubscribedEvents')->willReturn(['bar']);
        $eventSubscribers[3] = $this->createMock(EventSubscriber::class);
        $eventSubscribers[3]->expects($this->any())->method('getSubscribedEvents')->willReturn(['bar']);

        $fooFn = $this->createMock(FunctionNode::class);
        $barFn = $this->createMock(FunctionNode::class);
        $bazFn = $this->createMock(FunctionNode::class);

        $fooType = $this->createMock(Type::class);
        $barType = $this->createMock(Type::class);
        $bazType = $this->createMock(Type::class);

        $entityManagerParameters = $this->createMock(EntityManagerParametersInterface::class);
        $entityManagerParameters->expects($this->once())->method('getDsn')->willReturn('?driverClass=' . $connectionDriver::class);
        $entityManagerParameters->expects($this->once())->method('getEntityDirectories')->willReturn(['/entities']);
        $entityManagerParameters->expects($this->once())->method('getNamingStrategy')->willReturn($namingStrategy);
        $entityManagerParameters->expects($this->once())->method('getSchemaAssetsFilter')->willReturn(static fn() => false);
        $entityManagerParameters->expects($this->once())->method('getSchemaIgnoreClasses')->willReturn([stdClass::class]);
        $entityManagerParameters->expects($this->once())->method('getProxyDirectory')->willReturn('/proxies');
        $entityManagerParameters->expects($this->once())->method('getProxyNamespace')->willReturn('EntityProxy');
        $entityManagerParameters->expects($this->once())->method('getProxyAutogenerate')->willReturn(ProxyFactory::AUTOGENERATE_NEVER);
        $entityManagerParameters->expects($this->once())->method('getMetadataCache')->willReturn($metadataCache);
        $entityManagerParameters->expects($this->once())->method('getQueryCache')->willReturn($queryCache);
        $entityManagerParameters->expects($this->once())->method('getResultCache')->willReturn($resultCache);
        $entityManagerParameters->expects($this->once())->method('getCustomDatetimeFunctions')->willReturn(['foo' => $fooFn::class]);
        $entityManagerParameters->expects($this->once())->method('getCustomNumericFunctions')->willReturn(['bar' => $barFn::class]);
        $entityManagerParameters->expects($this->once())->method('getCustomStringFunctions')->willReturn(['baz' => $bazFn::class]);
        $entityManagerParameters->expects($this->once())->method('getMiddlewares')->willReturn($middlewares);
        $entityManagerParameters->expects($this->once())->method('getEventSubscribers')->willReturn($eventSubscribers);

        $entityManagerParameters->expects($this->once())->method('getConfigurators')->willReturn([
            static function (Configuration $configuration): void {
                $configuration->setAutoCommit(false);
            }
        ]);

        $entityManagerParameters->expects($this->once())->method('getTypes')->willReturn([
            $fooType::class => $fooType::class,
            $barType::class => $barType::class,
            $bazType::class => $bazType::class,
        ]);

        $entityManager = (new EntityManagerFactory())->createEntityManagerFromParameters($entityManagerParameters);

        self::assertInstanceOf(AttributeDriver::class, $entityManager->getConfiguration()->getMetadataDriverImpl());
        self::assertSame(['/entities'], $entityManager->getConfiguration()->getMetadataDriverImpl()->getPaths());
        self::assertSame($namingStrategy, $entityManager->getConfiguration()->getNamingStrategy());
        self::assertSame(false, ($entityManager->getConfiguration()->getSchemaAssetsFilter())(null));
        self::assertSame([stdClass::class], $entityManager->getConfiguration()->getSchemaIgnoreClasses());
        self::assertSame('/proxies', $entityManager->getConfiguration()->getProxyDir());
        self::assertSame('EntityProxy', $entityManager->getConfiguration()->getProxyNamespace());
        self::assertSame(ProxyFactory::AUTOGENERATE_NEVER, $entityManager->getConfiguration()->getAutoGenerateProxyClasses());
        self::assertSame($metadataCache, $entityManager->getConfiguration()->getMetadataCache());
        self::assertSame($queryCache, $entityManager->getConfiguration()->getQueryCache());
        self::assertSame($resultCache, $entityManager->getConfiguration()->getResultCache());
        self::assertSame($fooFn::class, $entityManager->getConfiguration()->getCustomDatetimeFunction('foo'));
        self::assertSame($barFn::class, $entityManager->getConfiguration()->getCustomNumericFunction('bar'));
        self::assertSame($bazFn::class, $entityManager->getConfiguration()->getCustomStringFunction('baz'));

        self::assertSame($connectionDriver::class, $entityManager->getConnection()->getDriver()::class);
        self::assertSame($resultCache, $entityManager->getConnection()->getConfiguration()->getResultCache());
        self::assertEquals([$eventSubscribers[0], $eventSubscribers[1]], array_values($entityManager->getEventManager()->getListeners('foo')));
        self::assertEquals([$eventSubscribers[2], $eventSubscribers[3]], array_values($entityManager->getEventManager()->getListeners('bar')));
        self::assertSame($middlewares, $entityManager->getConnection()->getConfiguration()->getMiddlewares());
        self::assertSame(false, $entityManager->getConnection()->getConfiguration()->getAutoCommit());

        self::assertSame($fooType::class, Type::getType($fooType::class)::class);
        self::assertSame($barType::class, Type::getType($barType::class)::class);
        self::assertSame($bazType::class, Type::getType($bazType::class)::class);
    }
}
