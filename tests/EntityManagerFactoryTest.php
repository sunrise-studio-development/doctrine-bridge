<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Driver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\NamingStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Sunrise\Bridge\Doctrine\EntityManagerFactory;
use Sunrise\Bridge\Doctrine\EntityManagerParametersInterface;

use function array_values;

final class EntityManagerFactoryTest extends TestCase
{
    public function testCreatesEntityManagerFromParameters(): void
    {
        $connectionDriver = $this->createMock(Driver::class);
        $metadataCache = $this->createMock(CacheItemPoolInterface::class);
        $queryCache = $this->createMock(CacheItemPoolInterface::class);
        $resultCache = $this->createMock(CacheItemPoolInterface::class);
        $namingStrategy = $this->createMock(NamingStrategy::class);

        $eventSubscribers = [];
        $eventSubscribers[0] = $this->createMock(EventSubscriber::class);
        $eventSubscribers[0]->expects($this->any())->method('getSubscribedEvents')->willReturn(['foo']);
        $eventSubscribers[1] = $this->createMock(EventSubscriber::class);
        $eventSubscribers[1]->expects($this->any())->method('getSubscribedEvents')->willReturn(['foo']);
        $eventSubscribers[2] = $this->createMock(EventSubscriber::class);
        $eventSubscribers[2]->expects($this->any())->method('getSubscribedEvents')->willReturn(['bar']);
        $eventSubscribers[3] = $this->createMock(EventSubscriber::class);
        $eventSubscribers[3]->expects($this->any())->method('getSubscribedEvents')->willReturn(['bar']);

        $middlewares = [];
        $middlewares[0] = $this->createMock(Driver\Middleware::class);
        $middlewares[0]->expects($this->any())->method('wrap')->with($connectionDriver)->willReturn($connectionDriver);
        $middlewares[1] = $this->createMock(Driver\Middleware::class);
        $middlewares[1]->expects($this->any())->method('wrap')->with($connectionDriver)->willReturn($connectionDriver);

        $entityManagerParameters = $this->createMock(EntityManagerParametersInterface::class);
        $entityManagerParameters->expects($this->once())->method('getDsn')->willReturn('?driverClass=' . $connectionDriver::class);
        $entityManagerParameters->expects($this->once())->method('getEntityDirectories')->willReturn(['/entities']);
        $entityManagerParameters->expects($this->once())->method('getProxyDirectory')->willReturn('/proxies');
        $entityManagerParameters->expects($this->once())->method('getProxyNamespace')->willReturn('EntityProxy');
        $entityManagerParameters->expects($this->once())->method('getProxyAutogenerate')->willReturn(0);
        $entityManagerParameters->expects($this->once())->method('getMetadataCache')->willReturn($metadataCache);
        $entityManagerParameters->expects($this->once())->method('getQueryCache')->willReturn($queryCache);
        $entityManagerParameters->expects($this->once())->method('getResultCache')->willReturn($resultCache);
        $entityManagerParameters->expects($this->once())->method('getNamingStrategy')->willReturn($namingStrategy);
        $entityManagerParameters->expects($this->once())->method('getEventSubscribers')->willReturn($eventSubscribers);
        $entityManagerParameters->expects($this->once())->method('getMiddlewares')->willReturn($middlewares);

        $entityManager = (new EntityManagerFactory())->createEntityManagerFromParameters($entityManagerParameters);

        self::assertInstanceOf(AttributeDriver::class, $entityManager->getConfiguration()->getMetadataDriverImpl());
        self::assertSame(['/entities'], $entityManager->getConfiguration()->getMetadataDriverImpl()->getPaths());
        self::assertSame('/proxies', $entityManager->getConfiguration()->getProxyDir());
        self::assertSame('EntityProxy', $entityManager->getConfiguration()->getProxyNamespace());
        self::assertSame(0, $entityManager->getConfiguration()->getAutoGenerateProxyClasses());
        self::assertSame($metadataCache, $entityManager->getConfiguration()->getMetadataCache());
        self::assertSame($queryCache, $entityManager->getConfiguration()->getQueryCache());
        self::assertSame($resultCache, $entityManager->getConfiguration()->getResultCache());
        self::assertSame($namingStrategy, $entityManager->getConfiguration()->getNamingStrategy());
        self::assertSame($connectionDriver::class, $entityManager->getConnection()->getDriver()::class);
        self::assertSame($resultCache, $entityManager->getConnection()->getConfiguration()->getResultCache());
        self::assertSame($middlewares, $entityManager->getConnection()->getConfiguration()->getMiddlewares());
        self::assertEquals([$eventSubscribers[0], $eventSubscribers[1]], array_values($entityManager->getEventManager()->getListeners('foo')));
        self::assertEquals([$eventSubscribers[2], $eventSubscribers[3]], array_values($entityManager->getEventManager()->getListeners('bar')));
    }
}
