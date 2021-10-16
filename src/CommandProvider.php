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
use Doctrine\Migrations\Configuration\EntityManager\ManagerRegistryEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Persistence\ManagerRegistry as EntityManagerRegistryInterface;
use Psr\Log\LoggerInterface;

/**
 * CommandProvider
 */
final class CommandProvider
{

    /**
     * @var class-string[]
     */
    private const DBAL_COMMANDS = [
        \Doctrine\DBAL\Tools\Console\Command\ReservedWordsCommand::class,
        \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand::class,
    ];

    /**
     * @var class-string[]
     */
    private const ORM_COMMANDS = [
        \Doctrine\ORM\Tools\Console\Command\ClearCache\CollectionRegionCommand::class,
        \Doctrine\ORM\Tools\Console\Command\ClearCache\EntityRegionCommand::class,
        \Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand::class,
        \Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand::class,
        \Doctrine\ORM\Tools\Console\Command\ClearCache\QueryRegionCommand::class,
        \Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand::class,
        \Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand::class,
        \Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand::class,
        \Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand::class,
        \Doctrine\ORM\Tools\Console\Command\InfoCommand::class,
        \Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand::class,
        \Doctrine\ORM\Tools\Console\Command\RunDqlCommand::class,
        \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand::class,
        \Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand::class,
        \Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand::class,
        \Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand::class,
    ];

    /**
     * @var class-string[]
     */
    private const MIGRATIONS_COMMANDS = [
        \Doctrine\Migrations\Tools\Console\Command\CurrentCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\DiffCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\ExecuteCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\GenerateCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\LatestCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\ListCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\MigrateCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\RollupCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\StatusCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\UpToDateCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\VersionCommand::class,
    ];

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
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getDbalCommands() : array
    {
        $connectionProvider = new ConnectionProvider($this->entityManagerRegistry);

        $commands = [];
        foreach (self::DBAL_COMMANDS as $className) {
            $commands[] = new $className($connectionProvider);
        }

        return $commands;
    }

    /**
     * Gets all ORM commands
     *
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getOrmCommands() : array
    {
        $entityManagerProvider = new EntityManagerProvider($this->entityManagerRegistry);

        $commands = [];
        foreach (self::ORM_COMMANDS as $className) {
            $commands[] = new $className($entityManagerProvider);
        }

        return $commands;
    }

    /**
     * Gets all migrations's commands
     *
     * @param array<string, mixed> $parameters
     * @param LoggerInterface|null $logger
     *
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getMigrationsCommands(array $parameters, ?LoggerInterface $logger = null) : array
    {
        // TODO: maybe this code should be moved somewhere...
        $configuration = new ConfigurationArray($parameters);
        $entityManagerLoader = ManagerRegistryEntityManager::withSimpleDefault($this->entityManagerRegistry);
        $dependencyFactory = DependencyFactory::fromEntityManager($configuration, $entityManagerLoader, $logger);

        $commands = [];
        foreach (self::MIGRATIONS_COMMANDS as $className) {
            $commands[] = new $className($dependencyFactory);
        }

        return $commands;
    }
}
