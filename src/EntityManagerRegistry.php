<?php

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Nekhay <afenric@gmail.com>
 * @copyright Copyright (c) 2025, Anatoly Nekhay
 * @license https://github.com/sunrise-studio-development/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-studio-development/doctrine-bridge
 */

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sunrise\Bridge\Doctrine\Exception\EntityManagerNotConfiguredException;
use Throwable;

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
        private readonly ?LoggerInterface $logger,
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

            $this->logger?->warning('A closed entity manager was detected and shut down.', [
                'em' => $entityManagerKey,
            ]);

            return;
        }

        $connection = $this->entityManagers[$entityManagerKey]->getConnection();
        if ($connection->isConnected()) {
            try {
                $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());
            } catch (Throwable $e) {
                $connection->close();

                $this->logger?->warning('An unstable database connection was detected and closed.', [
                    'em' => $entityManagerKey,
                    'error' => $e,
                ]);
            }
        }
    }
}
