<?php declare(strict_types=1);

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2020, Anatoly Fenric
 * @license https://github.com/sunrise-php/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-php/doctrine-bridge
 */

namespace Sunrise\Bridge\Doctrine;

/**
 * Import classes
 */
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider as EntityManagerProviderInterface;
use Doctrine\Persistence\ManagerRegistry as EntityManagerRegistryInterface;

/**
 * EntityManagerProvider
 */
final class EntityManagerProvider implements EntityManagerProviderInterface
{

    /**
     * @var EntityManagerRegistryInterface
     */
    private $entityManagerRegistry;

    /**
     * @param EntityManagerRegistryInterface $entityManagerRegistry
     */
    public function __construct(EntityManagerRegistryInterface $entityManagerRegistry)
    {
        $this->entityManagerRegistry = $entityManagerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultManager() : EntityManagerInterface
    {
        return $this->getManager($this->entityManagerRegistry->getDefaultManagerName());
    }

    /**
     * {@inheritdoc}
     */
    public function getManager(string $name) : EntityManagerInterface
    {
        return $this->entityManagerRegistry->getManager($name);
    }
}
