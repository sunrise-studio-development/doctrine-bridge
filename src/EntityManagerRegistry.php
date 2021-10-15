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
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Persistence\AbstractManagerRegistry as AbstractEntityManagerRegistry;
use Doctrine\Persistence\ManagerRegistry as EntityManagerRegistryInterface;
use InvalidArgumentException;

/**
 * Import functions
 */
use function key;
use function reset;
use function sprintf;

/**
 * EntityManagerRegistry
 */
final class EntityManagerRegistry extends AbstractEntityManagerRegistry implements EntityManagerRegistryInterface
{

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var EntityManagerFactory
     */
    private $entityManagerFactory;

    /**
     * @var array
     */
    private $serviceParameters = [];

    /**
     * @var \Closure[]
     */
    private $serviceFactories = [];

    /**
     * @var array<Connection|EntityManagerInterface>
     */
    private $services = [];

    /**
     * Initializes the registry
     *
     * @param array $configuration
     * @param string|null $registryName
     */
    public function __construct(array $configuration, ?string $registryName = null)
    {
        $this->connectionFactory = new ConnectionFactory();
        $this->entityManagerFactory = new EntityManagerFactory();

        $connectionServiceFactory = function (string $serviceName) : Connection {
            $parameters = $this->serviceParameters[$serviceName] ?? [];
            return $this->connectionFactory->createConnection($parameters);
        };

        $entityManagerServiceFactory = function (string $serviceName) : EntityManagerInterface {
            $connection = $this->getConnection($serviceName);
            $parameters = $this->serviceParameters[$serviceName] ?? [];
            return $this->entityManagerFactory->createEntityManager($connection, $parameters);
        };

        $connectionNames = [];
        $entityManagerNames = [];

        foreach ($configuration as $serviceName => $serviceParameters) {
            $serviceParameters = (array) $serviceParameters;

            if (isset($serviceParameters['dbal'])) {
                $connectionName = $serviceName . '.conn';
                $connectionNames[$serviceName] = $connectionName;
                $this->serviceParameters[$connectionName] = (array) $serviceParameters['dbal'];
                $this->serviceFactories[$connectionName] = $connectionServiceFactory;
            }

            if (isset($serviceParameters['orm'])) {
                $entityManagerName = $serviceName;
                $entityManagerNames[$serviceName] = $entityManagerName;
                $this->serviceParameters[$entityManagerName] = (array) $serviceParameters['orm'];
                $this->serviceFactories[$entityManagerName] = $entityManagerServiceFactory;
            }

            if (isset($serviceParameters['types'])) {
                foreach ($serviceParameters['types'] as $typeName => $className) {
                    Type::hasType($typeName) ?
                    Type::overrideType($typeName, $className) :
                    Type::addType($typeName, $className);
                }
            }
        }

        reset($connectionNames);
        reset($entityManagerNames);

        parent::__construct(
            $registryName ?? 'ORM',
            $connectionNames,
            $entityManagerNames,
            key($connectionNames) ?? 'default',
            key($entityManagerNames) ?? 'default',
            Proxy::class
        );
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    protected function getService($name)
    {
        if (!isset($this->services[$name])) {
            if (!isset($this->serviceFactories[$name])) {
                throw new InvalidArgumentException(sprintf(
                    'No service found for the %s name.',
                    $name
                ));
            }

            $this->services[$name] = $this->serviceFactories[$name]($name);
        }

        return $this->services[$name];
    }

    /**
     * {@inheritdoc}
     */
    protected function resetService($name)
    {
        unset($this->services[$name]);
    }
}
