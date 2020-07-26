<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge;

/**
 * Import classes
 */
use DI\Container;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\AbstractManagerRegistry;
use Ramsey\Uuid\Doctrine\UuidType;
use Ramsey\Uuid\Doctrine\UuidBinaryType;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;

/**
 * Import functions
 */
use function key;
use function reset;
use function sprintf;
use function sys_get_temp_dir;

/**
 * ManagerRegistry
 */
class ManagerRegistry extends AbstractManagerRegistry
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var \Closure[]
     */
    private $factories = [];

    /**
     * @var object[]
     */
    private $services = [];

    /**
     * Constructor of the class
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $configuration = $this->container->get('doctrine.configuration');
        $connections = [];
        $managers = [];
        $types = [];

        foreach ($configuration as $serviceName => $params) {
            $connectionName = sprintf('connection:%s', $serviceName);
            $managerName = sprintf('manager:%s', $serviceName);

            $connections[$serviceName] = $connectionName;
            $managers[$serviceName] = $managerName;

            $this->factories[$connectionName] = function () use ($serviceName) {
                return $this->getManager($serviceName)->getConnection();
            };

            $this->factories[$managerName] = function () use ($params) {
                return $this->createManager($params);
            };

            if (!empty($params['types'])) {
                $types += $params['types'];
            }
        }

        reset($connections);
        reset($managers);

        parent::__construct('ORM', $connections, $managers, key($connections), key($managers), Proxy::class);

        $this->registerUserTypes($types);
        $this->registerUuidType();
    }

    /**
     * Re-opens all closed managers
     *
     * It will be useful for long-running applications.
     *
     * @return void
     */
    public function reopenManagers() : void
    {
        foreach ($this->getManagers() as $name => $manager) {
            $manager->isOpen() or $this->resetManager($name);
        }
    }

    /**
     * Clears all managers
     *
     * It will be useful for long-running applications.
     *
     * @return void
     */
    public function clearManagers() : void
    {
        foreach ($this->getManagers() as $manager) {
            $manager->clear();
        }
    }

    /**
     * Closes all connections
     *
     * It will be useful for long-running applications.
     *
     * @return void
     */
    public function closeConnections() : void
    {
        foreach ($this->getConnections() as $connection) {
            $connection->close();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAliasNamespace($alias)
    {
        foreach ($this->getManagers() as $manager) {
            try {
                return $manager->getConfiguration()->getEntityNamespace($alias);
            } catch (ORMException $e) {
            }
        }

        throw ORMException::unknownEntityNamespace($alias);
    }

    /**
     * Gets CLI commands
     *
     * @return \Symfony\Component\Console\Command\Command[]
     *
     * @see CommandsProvider::getCommands()
     */
    public function getCommands() : array
    {
        $provider = new CommandsProvider($this->container);

        return $provider->getCommands();
    }

    /**
     * Gets an array hydrator
     *
     * @param string $name
     *
     * @return ArrayHydrator
     *
     * @link https://github.com/pmill/doctrine-array-hydrator
     */
    public function getHydrator(string $name = null) : ArrayHydrator
    {
        return new ArrayHydrator($this->getManager($name));
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException If the registry doesn't contain the named service.
     */
    protected function getService($name)
    {
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        if (!isset($this->factories[$name])) {
            throw new \RuntimeException(
                sprintf('Doctrine Manager registry does not contain named service "%s"', $name)
            );
        }

        $this->services[$name] = $this->factories[$name]();

        return $this->services[$name];
    }

    /**
     * {@inheritDoc}
     */
    protected function resetService($name)
    {
        unset($this->services[$name]);
    }

    /**
     * @param array $params
     *
     * @return EntityManagerInterface
     */
    private function createManager(array $params) : EntityManagerInterface
    {
        $config = new Configuration();

        $config->setRepositoryFactory(new RepositoryFactory($this->container));
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($params['metadata_sources'], false));

        $config->setMetadataCacheImpl($params['metadata_cache'] ?? new ArrayCache());
        $config->setQueryCacheImpl($params['query_cache'] ?? new ArrayCache());
        $config->setResultCacheImpl($params['result_cache'] ?? new ArrayCache());

        $config->setProxyDir($params['proxy_dir'] ?? sys_get_temp_dir());
        $config->setProxyNamespace($params['proxy_namespace'] ?? 'DoctrineProxies');
        $config->setAutoGenerateProxyClasses($params['proxy_auto_generate'] ?? false);

        $config->setSQLLogger($params['sql_logger'] ?? null);

        return EntityManager::create($params['connection'], $config);
    }

    /**
     * @param array $types
     *
     * @return void
     */
    private function registerUserTypes(array $types) : void
    {
        foreach ($types as $name => $class) {
            $type = $this->container->has($class) ?
                $this->container->get($class) :
                new $class();

            Type::getTypeRegistry()->has($name) ?
            Type::getTypeRegistry()->override($name, $type) :
            Type::getTypeRegistry()->register($name, $type);
        }
    }

    /**
     * @return void
     */
    private function registerUuidType() : void
    {
        Type::hasType(UuidType::NAME) ?
        Type::overrideType(UuidType::NAME, UuidType::class) :
        Type::addType(UuidType::NAME, UuidType::class);

        Type::hasType(UuidBinaryType::NAME) ?
        Type::overrideType(UuidBinaryType::NAME, UuidBinaryType::class) :
        Type::addType(UuidBinaryType::NAME, UuidBinaryType::class);

        Type::hasType(UuidBinaryOrderedTimeType::NAME) ?
        Type::overrideType(UuidBinaryOrderedTimeType::NAME, UuidBinaryOrderedTimeType::class) :
        Type::addType(UuidBinaryOrderedTimeType::NAME, UuidBinaryOrderedTimeType::class);
    }
}
