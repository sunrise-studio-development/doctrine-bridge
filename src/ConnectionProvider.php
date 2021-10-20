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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\ConnectionProvider as ConnectionProviderInterface;
use Doctrine\Persistence\ManagerRegistry as EntityManagerRegistryInterface;

/**
 * ConnectionProvider
 */
final class ConnectionProvider implements ConnectionProviderInterface
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
    public function getDefaultConnection() : Connection
    {
        return $this->getConnection($this->entityManagerRegistry->getDefaultConnectionName());
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(string $name) : Connection
    {
        return $this->entityManagerRegistry->getConnection($name);
    }
}
