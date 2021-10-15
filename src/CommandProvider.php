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
use Doctrine\DBAL\Tools\Console\Command as DBALCommand;
use Doctrine\Migrations\Configuration\EntityManager\ManagerRegistryEntityManager as MigrationsEntityManagerProvider;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray as MigrationsConfiguration;
use Doctrine\Migrations\DependencyFactory as MigrationsDependencyFactory;
use Doctrine\Migrations\Tools\Console\Command as MigrationsCommand;
use Doctrine\ORM\Tools\Console\Command as ORMCommand;
use Doctrine\Persistence\ManagerRegistry as EntityManagerRegistryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;

/**
 * CommandProvider
 */
final class CommandProvider
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
     * Gets all DBAL commands
     *
     * @return Command[]
     */
    public function getDBALCommands() : array
    {
        $connectionProvider = new ConnectionProvider($this->entityManagerRegistry);

        return [
            new DBALCommand\ReservedWordsCommand($connectionProvider),
            new DBALCommand\RunSqlCommand($connectionProvider),
        ];
    }

    /**
     * Gets all ORM commands
     *
     * @return Command[]
     */
    public function getORMCommands() : array
    {
        $entityManagerProvider = new EntityManagerProvider($this->entityManagerRegistry);

        return [
            new ORMCommand\ConvertMappingCommand($entityManagerProvider),
            new ORMCommand\EnsureProductionSettingsCommand($entityManagerProvider),
            new ORMCommand\GenerateProxiesCommand($entityManagerProvider),
            new ORMCommand\InfoCommand($entityManagerProvider),
            new ORMCommand\MappingDescribeCommand($entityManagerProvider),
            new ORMCommand\RunDqlCommand($entityManagerProvider),
            new ORMCommand\ValidateSchemaCommand($entityManagerProvider),
            new ORMCommand\ClearCache\CollectionRegionCommand($entityManagerProvider),
            new ORMCommand\ClearCache\EntityRegionCommand($entityManagerProvider),
            new ORMCommand\ClearCache\MetadataCommand($entityManagerProvider),
            new ORMCommand\ClearCache\QueryCommand($entityManagerProvider),
            new ORMCommand\ClearCache\QueryRegionCommand($entityManagerProvider),
            new ORMCommand\ClearCache\ResultCommand($entityManagerProvider),
            new ORMCommand\SchemaTool\CreateCommand($entityManagerProvider),
            new ORMCommand\SchemaTool\DropCommand($entityManagerProvider),
            new ORMCommand\SchemaTool\UpdateCommand($entityManagerProvider),
        ];
    }

    /**
     * Gets all migration commands
     *
     * @param array $parameters
     * @param LoggerInterface|null $logger
     *
     * @return Command[]
     */
    public function getMigrationCommands(array $parameters, ?LoggerInterface $logger = null) : array
    {
        $config = new MigrationsConfiguration($parameters);
        $managerProvider = MigrationsEntityManagerProvider::withSimpleDefault($this->entityManagerRegistry);
        $dependencyFactory = MigrationsDependencyFactory::fromEntityManager($config, $managerProvider, $logger);

        return [
            new MigrationsCommand\CurrentCommand($dependencyFactory),
            new MigrationsCommand\DiffCommand($dependencyFactory),
            new MigrationsCommand\DumpSchemaCommand($dependencyFactory),
            new MigrationsCommand\ExecuteCommand($dependencyFactory),
            new MigrationsCommand\GenerateCommand($dependencyFactory),
            new MigrationsCommand\LatestCommand($dependencyFactory),
            new MigrationsCommand\ListCommand($dependencyFactory),
            new MigrationsCommand\MigrateCommand($dependencyFactory),
            new MigrationsCommand\RollupCommand($dependencyFactory),
            new MigrationsCommand\StatusCommand($dependencyFactory),
            new MigrationsCommand\SyncMetadataCommand($dependencyFactory),
            new MigrationsCommand\UpToDateCommand($dependencyFactory),
            new MigrationsCommand\VersionCommand($dependencyFactory),
        ];
    }
}
