<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge;

/**
 * Import classes
 */
use DI\Container;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\DBAL\Tools\Console\Command as DBALCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\ORM\Tools\Console\Command as ORMCommand;
use Doctrine\Migrations\Configuration\Configuration as MigrationConfiguration;
use Doctrine\Migrations\Tools\Console\Command as MigrationCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;

/**
 * Import functions
 */
use function get_class;

/**
 * CommandsProvider
 */
class CommandsProvider
{

    /**
     * The application container
     *
     * @var Container
     */
    private $container;

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
     * Gets all commands
     *
     * @return Command[]
     */
    public function getCommands() : array
    {
        return \array_merge(
            $this->getDBALCommands(),
            $this->getORMCommands(),
            $this->getMigrationCommands()
        );
    }

    /**
     * Gets commands of the Doctrine DBAL
     *
     * @return Command[]
     */
    public function getDBALCommands() : array
    {
        return [
            $this->configureDBALCommand(new DBALCommand\ImportCommand()),
            $this->configureDBALCommand(new DBALCommand\ReservedWordsCommand()),
            $this->configureDBALCommand(new DBALCommand\RunSqlCommand()),
        ];
    }

    /**
     * Gets commands of the Doctrine ORM
     *
     * @return Command[]
     */
    public function getORMCommands() : array
    {
        return [
            $this->configureORMCommand(new ORMCommand\ClearCache\CollectionRegionCommand()),
            $this->configureORMCommand(new ORMCommand\ClearCache\EntityRegionCommand()),
            $this->configureORMCommand(new ORMCommand\ClearCache\MetadataCommand()),
            $this->configureORMCommand(new ORMCommand\ClearCache\QueryCommand()),
            $this->configureORMCommand(new ORMCommand\ClearCache\QueryRegionCommand()),
            $this->configureORMCommand(new ORMCommand\ClearCache\ResultCommand()),
            $this->configureORMCommand(new ORMCommand\SchemaTool\CreateCommand()),
            $this->configureORMCommand(new ORMCommand\SchemaTool\DropCommand()),
            $this->configureORMCommand(new ORMCommand\SchemaTool\UpdateCommand()),
            $this->configureORMCommand(new ORMCommand\ConvertDoctrine1SchemaCommand()),
            $this->configureORMCommand(new ORMCommand\ConvertMappingCommand()),
            $this->configureORMCommand(new ORMCommand\EnsureProductionSettingsCommand()),
            $this->configureORMCommand(new ORMCommand\GenerateEntitiesCommand()),
            $this->configureORMCommand(new ORMCommand\GenerateProxiesCommand()),
            $this->configureORMCommand(new ORMCommand\GenerateRepositoriesCommand()),
            $this->configureORMCommand(new ORMCommand\InfoCommand()),
            $this->configureORMCommand(new ORMCommand\MappingDescribeCommand()),
            $this->configureORMCommand(new ORMCommand\RunDqlCommand()),
            $this->configureORMCommand(new ORMCommand\ValidateSchemaCommand()),
        ];
    }

    /**
     * Gets commands of the Doctrine Migrations
     *
     * @return Command[]
     */
    public function getMigrationCommands() : array
    {
        $configuration = $this->createMigrationConfiguration();

        return [
            $this->configureMigrationCommand(new MigrationCommand\DiffCommand(), $configuration),
            $this->configureMigrationCommand(new MigrationCommand\DumpSchemaCommand(), $configuration),
            $this->configureMigrationCommand(new MigrationCommand\ExecuteCommand(), $configuration),
            $this->configureMigrationCommand(new MigrationCommand\GenerateCommand(), $configuration),
            $this->configureMigrationCommand(new MigrationCommand\LatestCommand(), $configuration),
            $this->configureMigrationCommand(new MigrationCommand\MigrateCommand(), $configuration),
            $this->configureMigrationCommand(new MigrationCommand\RollupCommand(), $configuration),
            $this->configureMigrationCommand(new MigrationCommand\StatusCommand(), $configuration),
            $this->configureMigrationCommand(new MigrationCommand\UpToDateCommand(), $configuration),
            $this->configureMigrationCommand(new MigrationCommand\VersionCommand(), $configuration),
        ];
    }

    /**
     * @param Command $command
     *
     * @return Command
     */
    private function configureDBALCommand(Command $command) : Command
    {
        $doctrine = $this->container->get('doctrine');

        $command->addOption('service', null, InputOption::VALUE_REQUIRED, 'Doctrine Configuration Name');

        $command->setCode((function ($input, $output) use ($doctrine) {
            $this->getHelperSet()->set(new ConnectionHelper(
                $doctrine->getConnection($input->getOption('service'))
            ), 'db');

            return $this->execute($input, $output);
        })->bindTo($command, get_class($command)));

        return $command;
    }

    /**
     * @param Command $command
     *
     * @return Command
     */
    private function configureORMCommand(Command $command) : Command
    {
        $doctrine = $this->container->get('doctrine');

        $command->addOption('service', null, InputOption::VALUE_REQUIRED, 'Doctrine Configuration Name');

        $command->setCode((function ($input, $output) use ($doctrine) {
            $this->getHelperSet()->set(new ConnectionHelper(
                $doctrine->getConnection($input->getOption('service'))
            ), 'db');

            $this->getHelperSet()->set(new EntityManagerHelper(
                $doctrine->getManager($input->getOption('service'))
            ), 'em');

            return $this->execute($input, $output);
        })->bindTo($command, get_class($command)));

        return $command;
    }

    /**
     * @param Command $command
     * @param MigrationConfiguration $configuration
     *
     * @return Command
     */
    private function configureMigrationCommand(Command $command, MigrationConfiguration $configuration) : Command
    {
        $doctrine = $this->container->get('doctrine');

        $command->addOption('service', null, InputOption::VALUE_REQUIRED, 'Doctrine Configuration Name');

        $command->setMigrationConfiguration($configuration);

        $command->setCode((function ($input, $output) use ($doctrine) {
            $this->getHelperSet()->set(new EntityManagerHelper(
                $doctrine->getManager($input->getOption('service'))
            ), 'em');

            return $this->execute($input, $output);
        })->bindTo($command, get_class($command)));

        return $command;
    }

    /**
     * @return MigrationConfiguration
     */
    private function createMigrationConfiguration() : MigrationConfiguration
    {
        $input = new ArgvInput();

        $doctrine = $this->container->get('doctrine');

        $connection = $doctrine->getConnection(
            $input->getParameterOption(['--service'], null)
        );

        $configuration = new MigrationConfiguration($connection);
        $configuration->setMigrationsNamespace('App\Migration');
        $configuration->setMigrationsDirectory('src/Migration');
        $configuration->setAllOrNothing(true);

        return $configuration;
    }
}
