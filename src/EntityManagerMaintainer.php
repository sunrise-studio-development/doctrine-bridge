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
use Doctrine\Persistence\ManagerRegistry as EntityManagerRegistryInterface;

/**
 * EntityManagerMaintainer
 */
final class EntityManagerMaintainer
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
     * Closes all connections
     *
     * @return void
     */
    public function closeAllConnections() : void
    {
        foreach ($this->entityManagerRegistry->getManagers() as $manager) {
            $manager->getConnection()->close();
        }
    }

    /**
     * Clears all managers
     *
     * @return void
     */
    public function clearAllManagers() : void
    {
        foreach ($this->entityManagerRegistry->getManagers() as $manager) {
            $manager->clear();
        }
    }

    /**
     * Re-opens all closed managers
     *
     * @return void
     */
    public function reopenAllManagers() : void
    {
        foreach ($this->entityManagerRegistry->getManagers() as $name => $manager) {
            $manager->isOpen() or $this->entityManagerRegistry->resetManager($name);
        }
    }
}
