<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\NamingStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Sunrise\Bridge\Doctrine\EntityManagerFactory;
use Sunrise\Bridge\Doctrine\EntityManagerParametersInterface;

use function array_map;

final class EntityManagerFactoryTest extends TestCase
{
    public function testCreatesEntityManagerFromParameters(): void
    {
        $connectionDriver = $this->createMock(Driver::class);
        $metadataCache = $this->createMock(CacheItemPoolInterface::class);
        $queryCache = $this->createMock(CacheItemPoolInterface::class);
        $resultCache = $this->createMock(CacheItemPoolInterface::class);
        $namingStrategy = $this->createMock(NamingStrategy::class);

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

        $entityManager = (new EntityManagerFactory())->createEntityManagerFromParameters($entityManagerParameters);
        $entityManagerConfiguration = $entityManager->getConfiguration();
        $entityManagerConnection = $entityManager->getConnection();

        $metadataDriver = $entityManagerConfiguration->getMetadataDriverImpl();
        self::assertInstanceOf(AttributeDriver::class, $metadataDriver);
        self::assertSame(['/entities'], $metadataDriver->getPaths());

        self::assertSame('/proxies', $entityManagerConfiguration->getProxyDir());
        self::assertSame('EntityProxy', $entityManagerConfiguration->getProxyNamespace());
        self::assertSame(0, $entityManagerConfiguration->getAutoGenerateProxyClasses());
        self::assertSame($metadataCache, $entityManagerConfiguration->getMetadataCache());
        self::assertSame($queryCache, $entityManagerConfiguration->getQueryCache());
        self::assertSame($resultCache, $entityManagerConfiguration->getResultCache());
        self::assertSame($namingStrategy, $entityManagerConfiguration->getNamingStrategy());

        self::assertSame($connectionDriver::class, $entityManagerConnection->getDriver()::class);
        self::assertSame($resultCache, $entityManagerConnection->getConfiguration()->getResultCache());
    }

    public function testLogger(): void
    {
        $connectionDriver = $this->createMock(Driver::class);
        $logger = $this->createMock(LoggerInterface::class);

        $entityManagerParameters = $this->createMock(EntityManagerParametersInterface::class);
        $entityManagerParameters->expects($this->once())->method('getDsn')->willReturn('?driverClass=' . $connectionDriver::class);
        $entityManagerParameters->expects($this->once())->method('getProxyDirectory')->willReturn('/proxies');
        $entityManagerParameters->expects($this->once())->method('getProxyNamespace')->willReturn('EntityProxy');
        $entityManagerParameters->expects($this->once())->method('getLogger')->willReturn($logger);

        self::assertContains(LoggingMiddleware::class, array_map(
            static fn(MiddlewareInterface $middleware): string => $middleware::class,
            (new EntityManagerFactory())->createEntityManagerFromParameters($entityManagerParameters)
                ->getConnection()->getConfiguration()->getMiddlewares(),
        ));
    }
}
