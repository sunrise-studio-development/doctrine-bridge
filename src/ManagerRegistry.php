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
use function DI\factory;
use function key;
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

        foreach ($configuration as $name => $params) {
            $connections[$name] = sprintf('doctrine.connection.%s', $name);
            $managers[$name] = sprintf('doctrine.manager.%s', $name);

            $this->container->set($connections[$name], factory(function ($name) {
                return $this->getManager($name)->getConnection();
            })->parameter('name', $name));

            $this->container->set($managers[$name], factory(function ($params) {
                return $this->createManager($params);
            })->parameter('params', $params));

            if (!empty($params['types'])) {
                $types += $params['types'];
            }
        }

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
     */
    protected function getService($name)
    {
        return $this->container->get($name);
    }

    /**
     * {@inheritDoc}
     */
    protected function resetService($name)
    {
        $this->container->set($name, $this->container->make($name));
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
