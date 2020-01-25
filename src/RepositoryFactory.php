<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge;

/**
 * Import classes
 */
use DI\Container;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory as BaseRepositoryFactory;

/**
 * Import functions
 */
use function spl_object_hash;

/**
 * RepositoryFactory
 */
class RepositoryFactory implements BaseRepositoryFactory
{

    /**
     * The application container
     *
     * @var Container
     */
    private $container;

    /**
     * The list of EntityRepository instances
     *
     * @var \Doctrine\Common\Persistence\ObjectRepository[]
     */
    private $repositories = [];

    /**
     * Constructor of the class
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        $entityMetadata = $entityManager->getClassMetadata($entityName);
        $repositoryHash = $entityMetadata->getName() . spl_object_hash($entityManager);

        if (isset($this->repositories[$repositoryHash])) {
            return $this->repositories[$repositoryHash];
        }

        $customRepositoryClassName = $entityMetadata->customRepositoryClassName;
        $defaultRepositoryClassName = $entityManager->getConfiguration()->getDefaultRepositoryClassName();
        $determinedRepositoryClassName = $customRepositoryClassName ?: $defaultRepositoryClassName;

        $this->repositories[$repositoryHash] = new $determinedRepositoryClassName($entityManager, $entityMetadata);

        $this->container->injectOn($this->repositories[$repositoryHash]);

        return $this->repositories[$repositoryHash];
    }
}
