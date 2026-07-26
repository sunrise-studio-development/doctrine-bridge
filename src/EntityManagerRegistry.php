<?php

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatolii Nekhai <afenric@gmail.com>
 * @copyright Copyright (c) 2025, Anatolii Nekhai
 * @license https://github.com/sunrise-studio-development/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-studio-development/doctrine-bridge
 */

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DatabaseException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sunrise\Bridge\Doctrine\Exception\EntityManagerNotConfiguredException;

final class EntityManagerRegistry implements EntityManagerRegistryInterface
{
    /**
     * @var array<string, EntityManagerInterface>
     */
    private array $entityManagers = [];

    /**
     * @var array<string, EntityManagerParametersInterface>
     */
    private array $entityManagerParametersMap = [];

    /**
     * @param array<array-key, EntityManagerParametersInterface> $entityManagerParametersList
     */
    public function __construct(
        private readonly EntityManagerFactoryInterface $entityManagerFactory,
        array $entityManagerParametersList,
        private readonly EntityManagerNameInterface $defaultEntityManagerName,
        private readonly ?LoggerInterface $logger = null,
    ) {
        foreach ($entityManagerParametersList as $entityManagerParameters) {
            $entityManagerNameKey = $entityManagerParameters->getName()->getValue();
            $this->entityManagerParametersMap[$entityManagerNameKey] = $entityManagerParameters;
        }
    }

    public function hasEntityManager(?EntityManagerNameInterface $entityManagerName = null): bool
    {
        $entityManagerName ??= $this->defaultEntityManagerName;
        $entityManagerKey = $entityManagerName->getValue();

        return isset($this->entityManagers[$entityManagerKey]);
    }

    /**
     * @inheritDoc
     */
    public function getEntityManager(?EntityManagerNameInterface $entityManagerName = null): EntityManagerInterface
    {
        $entityManagerName ??= $this->defaultEntityManagerName;
        $entityManagerKey = $entityManagerName->getValue();

        $this->checkEntityManagerHealth($entityManagerKey);

        return $this->entityManagers[$entityManagerKey] ??= $this->entityManagerFactory
            ->createEntityManagerFromParameters(
                $this->entityManagerParametersMap[$entityManagerKey]
                    ?? throw new EntityManagerNotConfiguredException($entityManagerName)
            );
    }

    private function checkEntityManagerHealth(string $entityManagerKey): void
    {
        if (!isset($this->entityManagers[$entityManagerKey])) {
            return;
        }

        if (!$this->entityManagers[$entityManagerKey]->isOpen()) {
            unset($this->entityManagers[$entityManagerKey]);

            $this->logger?->warning('A closed entity manager was detected and discarded.', [
                'em' => $entityManagerKey,
            ]);

            return;
        }

        $databaseConnection = $this->entityManagers[$entityManagerKey]->getConnection();

        $this->checkDatabaseConnectionHealth($entityManagerKey, $databaseConnection);
    }

    private function checkDatabaseConnectionHealth(string $entityManagerKey, Connection $databaseConnection): void
    {
        if (!$databaseConnection->isConnected()) {
            return;
        }

        // Do not interfere with an active transaction; let connection failures surface naturally.
        if ($databaseConnection->isTransactionActive()) {
            return;
        }

        try {
            $databaseConnection->executeQuery($databaseConnection->getDatabasePlatform()->getDummySelectSQL());
        } catch (DatabaseException $e) {
            $databaseConnection->close();

            $this->logger?->warning('An unstable database connection was detected and closed.', [
                'em' => $entityManagerKey,
                'error' => $e,
            ]);
        }
    }
}
