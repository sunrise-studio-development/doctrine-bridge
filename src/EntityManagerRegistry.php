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
use Closure;
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
     * @var array<string, mixed>
     */
    private $serviceParameters = [];

    /**
     * @var Closure[]
     */
    private $serviceFactories = [];

    /**
     * @var Connection[]|EntityManagerInterface[]
     */
    private $services = [];

    /**
     * Initializes the registry
     *
     * @param array<string, mixed> $configuration
     * @param string|null $registryName
     */
    public function __construct(array $configuration, ?string $registryName = null)
    {
        $this->connectionFactory = new ConnectionFactory();
        $this->entityManagerFactory = new EntityManagerFactory();

        $connectionNames = [];
        $entityManagerNames = [];

        foreach ($configuration as $serviceName => $serviceParameters) {
            $serviceParameters = (array) $serviceParameters;

            if (isset($serviceParameters['dbal'])) {
                $connectionName = $serviceName . '.conn';
                $connectionNames[$serviceName] = $connectionName;
                $this->serviceParameters[$connectionName] = (array) $serviceParameters['dbal'];
                $this->serviceFactories[$connectionName] = $this->createConnectionServiceFactory();
            }

            if (isset($serviceParameters['orm'])) {
                $entityManagerName = $serviceName;
                $entityManagerNames[$serviceName] = $entityManagerName;
                $this->serviceParameters[$entityManagerName] = (array) $serviceParameters['orm'];
                $this->serviceFactories[$entityManagerName] = $this->createEntityManagerServiceFactory();
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
     * @param string|null $managerName
     *
     * @return EntityHydrator
     */
    public function getHydrator(?string $managerName = null) : EntityHydrator
    {
        return new EntityHydrator($this->getManager($managerName));
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
            // this case is controlled by the parent...
            if (!isset($this->serviceFactories[$name])) {
                // @codeCoverageIgnoreStart
                throw new InvalidArgumentException(sprintf(
                    'No service found for the %s name.',
                    $name
                ));
                // @codeCoverageIgnoreEnd
            }

            $this->services[$name] = ($this->serviceFactories[$name])($this, $name);
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

    /**
     * @return Closure
     */
    private function createConnectionServiceFactory() : Closure
    {
        return static function (self $scope, string $serviceName) : Connection {
            return $scope->connectionFactory->createConnection(
                $scope->serviceParameters[$serviceName] ?? []
            );
        };
    }

    /**
     * @return Closure
     */
    private function createEntityManagerServiceFactory() : Closure
    {
        return static function (self $scope, string $serviceName) : EntityManagerInterface {
            return $scope->entityManagerFactory->createEntityManager(
                $scope->getConnection($serviceName),
                $scope->serviceParameters[$serviceName] ?? []
            );
        };
    }
}
