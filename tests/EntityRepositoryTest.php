<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sunrise\Bridge\Doctrine\AbstractEntityRepository;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;

final class EntityRepositoryTest extends TestCase
{
    use TestKit;

    private EntityManagerRegistryInterface&MockObject $entityManagerRegistry;
    private EntityManagerNameInterface&MockObject $entityManagerName;
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $entityRepository;

    protected function setUp(): void
    {
        $this->entityManagerRegistry = $this->createMock(EntityManagerRegistryInterface::class);
        $this->entityManagerName = $this->mockEntityManagerName('a7109e9b-6ea9-4ca1-b431-3368400a9efe');
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);
    }

    public function testCreateQueryBuilder(): void
    {
        $entityRepository = self::createEntityRepository($this->entityManagerRegistry, $this->entityManagerName);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->entityManagerRegistry->expects($this->once())->method('getEntityManager')->with($this->entityManagerName)->willReturn($this->entityManager);
        $this->entityManager->expects($this->once())->method('getRepository')->with($entityRepository->getClassName())->willReturn($this->entityRepository);
        $this->entityRepository->expects($this->once())->method('createQueryBuilder')->with('alias', 'id')->willReturn($queryBuilder);

        self::assertSame($queryBuilder, $entityRepository->createQueryBuilder('alias', 'id'));
    }

    public function testCount(): void
    {
        $entityRepository = self::createEntityRepository($this->entityManagerRegistry, $this->entityManagerName);

        $this->entityManagerRegistry->expects($this->once())->method('getEntityManager')->with($this->entityManagerName)->willReturn($this->entityManager);
        $this->entityManager->expects($this->once())->method('getRepository')->with($entityRepository->getClassName())->willReturn($this->entityRepository);
        $this->entityRepository->expects($this->once())->method('count')->with(['foo' => 'bar'])->willReturn(1);

        self::assertSame(1, $entityRepository->count(['foo' => 'bar']));
    }

    public function testFind(): void
    {
        $entityRepository = self::createEntityRepository($this->entityManagerRegistry, $this->entityManagerName);
        $entity = new ($entityRepository->getClassName());

        $this->entityManagerRegistry->expects($this->once())->method('getEntityManager')->with($this->entityManagerName)->willReturn($this->entityManager);
        $this->entityManager->expects($this->once())->method('getRepository')->with($entityRepository->getClassName())->willReturn($this->entityRepository);
        $this->entityRepository->expects($this->once())->method('find')->with(1)->willReturn($entity);

        self::assertSame($entity, $entityRepository->find(1));
    }

    public function testFindOneBy(): void
    {
        $entityRepository = self::createEntityRepository($this->entityManagerRegistry, $this->entityManagerName);
        $entity = new ($entityRepository->getClassName());

        $this->entityManagerRegistry->expects($this->once())->method('getEntityManager')->with($this->entityManagerName)->willReturn($this->entityManager);
        $this->entityManager->expects($this->once())->method('getRepository')->with($entityRepository->getClassName())->willReturn($this->entityRepository);
        $this->entityRepository->expects($this->once())->method('findOneBy')->with(['foo' => 'bar'])->willReturn($entity);

        self::assertSame($entity, $entityRepository->findOneBy(['foo' => 'bar']));
    }

    public function testFindAll(): void
    {
        $entityRepository = self::createEntityRepository($this->entityManagerRegistry, $this->entityManagerName);
        $entity = new ($entityRepository->getClassName());

        $this->entityManagerRegistry->expects($this->once())->method('getEntityManager')->with($this->entityManagerName)->willReturn($this->entityManager);
        $this->entityManager->expects($this->once())->method('getRepository')->with($entityRepository->getClassName())->willReturn($this->entityRepository);
        $this->entityRepository->expects($this->once())->method('findAll')->with(/* nothing */)->willReturn([$entity]);

        self::assertSame([$entity], $entityRepository->findAll());
    }

    public function testFindBy(): void
    {
        $entityRepository = self::createEntityRepository($this->entityManagerRegistry, $this->entityManagerName);
        $entity = new ($entityRepository->getClassName());

        $this->entityManagerRegistry->expects($this->once())->method('getEntityManager')->with($this->entityManagerName)->willReturn($this->entityManager);
        $this->entityManager->expects($this->once())->method('getRepository')->with($entityRepository->getClassName())->willReturn($this->entityRepository);
        $this->entityRepository->expects($this->once())->method('findBy')->with(['foo' => 'bar'], ['foo' => 'asc'], 10, 0)->willReturn([$entity]);

        self::assertSame([$entity], $entityRepository->findBy(['foo' => 'bar'], ['foo' => 'asc'], 10, 0));
    }

    public function testMatching(): void
    {
        $entityRepository = self::createEntityRepository($this->entityManagerRegistry, $this->entityManagerName);
        $criteria = $this->createMock(Criteria::class);
        $criteriaCollection = $this->createMock(LazyCriteriaCollection::class);

        $this->entityManagerRegistry->expects($this->once())->method('getEntityManager')->with($this->entityManagerName)->willReturn($this->entityManager);
        $this->entityManager->expects($this->once())->method('getRepository')->with($entityRepository->getClassName())->willReturn($this->entityRepository);
        $this->entityRepository->expects($this->once())->method('matching')->with($criteria)->willReturn($criteriaCollection);

        self::assertSame($criteriaCollection, $entityRepository->matching($criteria));
    }

    public function testMagic(): void
    {
        $entityRepository = self::createEntityRepository($this->entityManagerRegistry, $this->entityManagerName);

        $this->entityManagerRegistry->expects($this->once())->method('getEntityManager')->with($this->entityManagerName)->willReturn($this->entityManager);
        $this->entityManager->expects($this->once())->method('getRepository')->with($entityRepository->getClassName())->willReturn($this->entityRepository);
        $this->entityRepository->expects($this->once())->method('__call')->with('findOneByFoo', ['bar'])->willReturn(null);

        self::assertNull($entityRepository->findOneByFoo('bar'));
    }

    private static function createEntityRepository(
        EntityManagerRegistryInterface $entityManagerRegistry,
        ?EntityManagerNameInterface $entityManagerName,
    ): AbstractEntityRepository {
        return new class (
            $entityManagerRegistry,
            $entityManagerName,
        ) extends AbstractEntityRepository {
            public function __construct(
                EntityManagerRegistryInterface $entityManagerRegistry,
                ?EntityManagerNameInterface $entityManagerName,
            ) {
                parent::__construct($entityManagerRegistry, $entityManagerName);
            }

            final public function getClassName(): string
            {
                return (new class {
                })::class;
            }
        };
    }
}
