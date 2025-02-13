<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\ORM\Mapping\NamingStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use SensitiveParameter;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerParameters;

final class EntityManagerParametersTest extends TestCase
{
    public function testConstructor(): void
    {
        $name = $this->createMock(EntityManagerNameInterface::class);
        $metadataCache = $this->createMock(CacheItemPoolInterface::class);
        $queryCache = $this->createMock(CacheItemPoolInterface::class);
        $resultCache = $this->createMock(CacheItemPoolInterface::class);
        $namingStrategy = $this->createMock(NamingStrategy::class);
        $logger = $this->createMock(LoggerInterface::class);

        $parameters = new EntityManagerParameters(
            name: $name,
            dsn: 'secret',
            entityDirectories: ['/entities'],
            proxyDirectory: '/proxies',
            proxyNamespace: 'EntityProxy',
            proxyAutogenerate: 0,
            metadataCache: $metadataCache,
            queryCache: $queryCache,
            resultCache: $resultCache,
            namingStrategy: $namingStrategy,
            logger: $logger,
        );

        self::assertSame($name, $parameters->getName());
        self::assertSame('secret', $parameters->getDsn());
        self::assertSame(['/entities'], $parameters->getEntityDirectories());
        self::assertSame('/proxies', $parameters->getProxyDirectory());
        self::assertSame('EntityProxy', $parameters->getProxyNamespace());
        self::assertSame(0, $parameters->getProxyAutogenerate());
        self::assertSame($metadataCache, $parameters->getMetadataCache());
        self::assertSame($queryCache, $parameters->getQueryCache());
        self::assertSame($resultCache, $parameters->getResultCache());
        self::assertSame($namingStrategy, $parameters->getNamingStrategy());
        self::assertSame($logger, $parameters->getLogger());
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
