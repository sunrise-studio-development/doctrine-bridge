<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sunrise\Bridge\Doctrine\EntityManagerFactoryInterface;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerParametersInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistry;
use Sunrise\Bridge\Doctrine\Exception\EntityManagerNotConfiguredException;
use Throwable;

final class EntityManagerRegistryTest extends TestCase
{
    use TestKit;

    private Connection&MockObject $mockedConnection;
    private EntityManagerInterface&MockObject $mockedEntityManager;
    private EntityManagerFactoryInterface&MockObject $mockedEntityManagerFactory;
    /** @var array<array-key, EntityManagerParametersInterface&MockObject> */
    private array $mockedEntityManagerParametersList;
    private EntityManagerParametersInterface&MockObject $mockedEntityManagerParameters;
    private EntityManagerNameInterface&MockObject $mockedDefaultEntityManagerName;
    private LoggerInterface&MockObject $mockedLogger;

    protected function setUp(): void
    {
        $this->mockedConnection = $this->createMock(Connection::class);
        $this->mockedEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockedEntityManagerFactory = $this->createMock(EntityManagerFactoryInterface::class);
        $this->mockedEntityManagerParametersList = [];
        $this->mockedEntityManagerParameters = $this->createMock(EntityManagerParametersInterface::class);
        $this->mockedDefaultEntityManagerName = $this->mockEntityManagerName('default');
        $this->mockedLogger = $this->createMock(LoggerInterface::class);
    }

    private function createEntityManagerRegistry(
        ?EntityManagerFactoryInterface $entityManagerFactory = null,
        ?array $entityManagerParametersList = null,
        ?EntityManagerNameInterface $defaultEntityManagerName = null,
        bool $withLogger = false,
    ): EntityManagerRegistry {
        return new EntityManagerRegistry(
            entityManagerFactory: $entityManagerFactory ?? $this->mockedEntityManagerFactory,
            entityManagerParametersList: $entityManagerParametersList ?? $this->mockedEntityManagerParametersList,
            defaultEntityManagerName: $defaultEntityManagerName ?? $this->mockedDefaultEntityManagerName,
            logger: $withLogger ? $this->mockedLogger : null,
        );
    }

    public function testUsesDefaultEntityManagerNameWhenNoneProvided(): void
    {
        $this->mockedEntityManagerParameters->expects($this->once())->method('getName')->willReturn($this->mockEntityManagerName('default', calls: 1));
        $this->mockedEntityManagerParametersList[] = $this->mockedEntityManagerParameters;
        $this->mockedEntityManagerFactory->expects($this->once())->method('createEntityManagerFromParameters')->with($this->mockedEntityManagerParameters)->willReturn($this->mockedEntityManager);
        $entityManagerRegistry = $this->createEntityManagerRegistry(defaultEntityManagerName: $this->mockEntityManagerName('default', calls: 1));
        self::assertFalse($entityManagerRegistry->hasEntityManager($this->mockEntityManagerName('default', calls: 1)));
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
        self::assertTrue($entityManagerRegistry->hasEntityManager($this->mockEntityManagerName('default', calls: 1)));
    }

    public function testUsesGivenEntityManagerNameInsteadOfDefault(): void
    {
        $this->mockedEntityManagerParameters->expects($this->once())->method('getName')->willReturn($this->mockEntityManagerName('foo', calls: 1));
        $this->mockedEntityManagerParametersList[] = $this->mockedEntityManagerParameters;
        $this->mockedEntityManagerFactory->expects($this->once())->method('createEntityManagerFromParameters')->with($this->mockedEntityManagerParameters)->willReturn($this->mockedEntityManager);
        $entityManagerRegistry = $this->createEntityManagerRegistry(defaultEntityManagerName: $this->mockEntityManagerName('default', calls: 0));
        self::assertFalse($entityManagerRegistry->hasEntityManager($this->mockEntityManagerName('foo', calls: 1)));
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager($this->mockEntityManagerName('foo', calls: 1)));
        self::assertTrue($entityManagerRegistry->hasEntityManager($this->mockEntityManagerName('foo', calls: 1)));
    }

    public function testDoesNotCheckEntityManagerHealthOnFirstCall(): void
    {
        $this->mockedEntityManagerParameters->expects($this->once())->method('getName')->willReturn($this->mockEntityManagerName('default', calls: 1));
        $this->mockedEntityManagerParametersList[] = $this->mockedEntityManagerParameters;
        $this->mockedEntityManagerFactory->expects($this->once())->method('createEntityManagerFromParameters')->with($this->mockedEntityManagerParameters)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->never())->method('isOpen');
        self::assertSame($this->mockedEntityManager, $this->createEntityManagerRegistry(defaultEntityManagerName: $this->mockEntityManagerName('default', calls: 1))->getEntityManager());
    }

    public function testChecksEntityManagerHealthOnSubsequentCalls(): void
    {
        $this->mockedEntityManagerParameters->expects($this->once())->method('getName')->willReturn($this->mockEntityManagerName('default', calls: 1));
        $this->mockedEntityManagerParametersList[] = $this->mockedEntityManagerParameters;
        $this->mockedEntityManagerFactory->expects($this->once())->method('createEntityManagerFromParameters')->with($this->mockedEntityManagerParameters)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->exactly(2))->method('isOpen')->willReturn(true);
        $entityManagerRegistry = $this->createEntityManagerRegistry(defaultEntityManagerName: $this->mockEntityManagerName('default', calls: 3));
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
    }

    public function testDoesNotCheckConnectionHealthOnFirstCall(): void
    {
        $this->mockedEntityManagerParameters->expects($this->once())->method('getName')->willReturn($this->mockEntityManagerName('default', calls: 1));
        $this->mockedEntityManagerParametersList[] = $this->mockedEntityManagerParameters;
        $this->mockedEntityManagerFactory->expects($this->once())->method('createEntityManagerFromParameters')->with($this->mockedEntityManagerParameters)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->any())->method('getConnection')->willReturn($this->mockedConnection);
        $this->mockedConnection->expects($this->never())->method('isConnected');
        self::assertSame($this->mockedEntityManager, $this->createEntityManagerRegistry(defaultEntityManagerName: $this->mockEntityManagerName('default', calls: 1))->getEntityManager());
    }

    public function testChecksConnectionHealthOnSubsequentCalls(): void
    {
        $this->mockedEntityManagerParameters->expects($this->once())->method('getName')->willReturn($this->mockEntityManagerName('default', calls: 1));
        $this->mockedEntityManagerParametersList[] = $this->mockedEntityManagerParameters;
        $this->mockedEntityManagerFactory->expects($this->once())->method('createEntityManagerFromParameters')->with($this->mockedEntityManagerParameters)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->any())->method('isOpen')->willReturn(true);
        $this->mockedEntityManager->expects($this->exactly(2))->method('getConnection')->willReturn($this->mockedConnection);
        $this->mockedConnection->expects($this->exactly(2))->method('isConnected')->willReturn(false);
        $entityManagerRegistry = $this->createEntityManagerRegistry(defaultEntityManagerName: $this->mockEntityManagerName('default', calls: 3));
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
    }

    public function testReopensClosedEntityManager(): void
    {
        $this->mockedEntityManagerParameters->expects($this->once())->method('getName')->willReturn($this->mockEntityManagerName('default', calls: 1));
        $this->mockedEntityManagerParametersList[] = $this->mockedEntityManagerParameters;
        $this->mockedEntityManagerFactory->expects($this->exactly(3))->method('createEntityManagerFromParameters')->with($this->mockedEntityManagerParameters)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->exactly(2))->method('isOpen')->willReturn(false);
        $this->mockedLogger->expects($this->exactly(2))->method('warning')->with('A closed entity manager was detected and shut down.', ['em' => 'default']);
        $entityManagerRegistry = $this->createEntityManagerRegistry(defaultEntityManagerName: $this->mockEntityManagerName('default', calls: 3), withLogger: true);
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
    }

    public function testClosesUnstableDatabaseConnection(): void
    {
        $this->mockedEntityManagerParameters->expects($this->once())->method('getName')->willReturn($this->mockEntityManagerName('default', calls: 1));
        $this->mockedEntityManagerParametersList[] = $this->mockedEntityManagerParameters;
        $this->mockedEntityManagerFactory->expects($this->once())->method('createEntityManagerFromParameters')->with($this->mockedEntityManagerParameters)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->any())->method('isOpen')->willReturn(true);
        $this->mockedEntityManager->expects($this->exactly(2))->method('getConnection')->willReturn($this->mockedConnection);
        $this->mockedConnection->expects($this->exactly(2))->method('isConnected')->willReturn(true);
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects($this->exactly(2))->method('getDummySelectSQL')->willReturn('SELECT 1');
        $this->mockedConnection->expects($this->exactly(2))->method('getDatabasePlatform')->willReturn($platform);
        $error = $this->createMock(Throwable::class);
        $this->mockedConnection->expects($this->exactly(2))->method('executeQuery')->with('SELECT 1')->willThrowException($error);
        $this->mockedConnection->expects($this->exactly(2))->method('close');
        $this->mockedLogger->expects($this->exactly(2))->method('warning')->with('An unstable database connection was detected and closed.', ['em' => 'default', 'error' => $error]);
        $entityManagerRegistry = $this->createEntityManagerRegistry(defaultEntityManagerName: $this->mockEntityManagerName('default', calls: 3), withLogger: true);
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
        self::assertSame($this->mockedEntityManager, $entityManagerRegistry->getEntityManager());
    }

    public function testNotConfiguredEntityManager(): void
    {
        $this->mockedEntityManagerFactory->expects($this->never())->method('createEntityManagerFromParameters');
        $entityManagerRegistry = $this->createEntityManagerRegistry();
        $this->expectException(EntityManagerNotConfiguredException::class);
        $this->expectExceptionMessage('The entity manager "unknown" is not configured.');
        $entityManagerRegistry->getEntityManager($this->mockEntityManagerName('unknown', calls: 2));
    }
}
