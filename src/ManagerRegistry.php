<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge;

/**
 * Import classes
 */
use DI\Container;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\AbstractManagerRegistry;

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
        $configuration = $container->get('doctrine.configuration');
        $connections = [];
        $managers = [];

        foreach ($configuration as $name => $params) {
            $connections[$name] = sprintf('doctrine.connection.%s', $name);
            $managers[$name] = sprintf('doctrine.manager.%s', $name);

            $container->set($connections[$name], $this->getConnectionFactory($name));
            $container->set($managers[$name], $this->getManagerFactory($params));
        }

        parent::__construct('ORM', $connections, $managers, key($connections), key($managers), Proxy::class);

        $this->container = $container;
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
     * Returns a factory for Doctrine Connection
     *
     * @param string $name
     *
     * @return object See `DI\factory()`
     */
    private function getConnectionFactory(string $name)
    {
        return factory(function ($name) {
            return $this->getManager($name)->getConnection();
        })->parameter('name', $name);
    }

    /**
     * Returns a factory for Doctrine Manager
     *
     * @param array $params
     *
     * @return object See `DI\factory()`
     */
    private function getManagerFactory(array $params)
    {
        return factory(function ($params) {
            return EntityManager::create($params['connection'], $this->createManagerConfiguration($params));
        })->parameter('params', $params);
    }

    /**
     * Creates the Doctrine Configuration from the given parameters
     *
     * @param array $params
     *
     * @return Configuration
     */
    private function createManagerConfiguration(array $params) : Configuration
    {
        $config = new Configuration();

        $config->setMetadataDriverImpl(
            $config->newDefaultAnnotationDriver($params['metadata_sources'], false)
        );

        $config->setMetadataCacheImpl($params['metadata_cache'] ?? new ArrayCache());
        $config->setQueryCacheImpl($params['query_cache'] ?? new ArrayCache());
        $config->setResultCacheImpl($params['result_cache'] ?? new ArrayCache());

        $config->setProxyDir($params['proxy_dir'] ?? sys_get_temp_dir());
        $config->setProxyNamespace($params['proxy_namespace'] ?? 'Acme\\Proxy\\Entity');
        $config->setAutoGenerateProxyClasses($params['proxy_auto_generate'] ?? false);

        $config->setRepositoryFactory(
            new RepositoryFactory($this->container)
        );

        return $config;
    }
}
