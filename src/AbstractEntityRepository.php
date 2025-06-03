<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;

/**
 * @template T of object
 * @implements ObjectRepository<T>
 * @implements Selectable<int, T>
 *
 * @since 3.4.0
 */
abstract class AbstractEntityRepository implements ObjectRepository, Selectable
{
    public function __construct(
        private readonly EntityManagerRegistryInterface $entityManagerRegistry,
        private readonly ?EntityManagerNameInterface $entityManagerName = null,
    ) {
    }

    final public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManagerRegistry->getEntityManager($this->entityManagerName);
    }

    /**
     * Returns the original entity repository.
     *
     * @return EntityRepository<T>
     */
    final public function getEntityRepository(): EntityRepository
    {
        return $this->getEntityManager()->getRepository($this->getClassName());
    }

    /**
     * @see EntityRepository::createQueryBuilder()
     */
    final public function createQueryBuilder(string $alias, ?string $indexBy = null): QueryBuilder
    {
        return $this->getEntityRepository()->createQueryBuilder($alias, $indexBy);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @see EntityRepository::count()
     */
    final public function count(array $criteria = []): int
    {
        return $this->getEntityRepository()->count($criteria);
    }

    /**
     * @inheritDoc
     *
     * @see EntityRepository::find()
     */
    final public function find(mixed $id): ?object
    {
        return $this->getEntityRepository()->find($id);
    }

    /**
     * @inheritDoc
     *
     * @see EntityRepository::findOneBy()
     */
    final public function findOneBy(array $criteria): ?object
    {
        return $this->getEntityRepository()->findOneBy($criteria);
    }

    /**
     * @inheritDoc
     *
     * @see EntityRepository::findAll()
     */
    final public function findAll(): array
    {
        return $this->getEntityRepository()->findAll();
    }

    /**
     * @inheritDoc
     *
     * @see EntityRepository::findBy()
     */
    final public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->getEntityRepository()->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @inheritDoc
     *
     * @see EntityRepository::matching()
     */
    final public function matching(Criteria $criteria)
    {
        return $this->getEntityRepository()->matching($criteria);
    }

    /**
     * @param list<mixed> $arguments
     *
     * @see EntityRepository::__call()
     */
    final public function __call(string $method, array $arguments): mixed
    {
        return $this->getEntityRepository()->__call($method, $arguments);
    }
}
