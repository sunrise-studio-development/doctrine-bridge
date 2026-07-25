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

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Repository\RepositoryFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use SensitiveParameter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final readonly class EntityManagerParameters implements EntityManagerParametersInterface
{
    public function __construct(
        private EntityManagerNameInterface $name,
        #[SensitiveParameter]
        private string $dsn,
        /** @var array<array-key, class-string<Type>> */
        private array $types = [],
        /** @var array<array-key, Middleware> */
        private array $middlewares = [],
        /** @var array<array-key, string> */
        private array $entityDirectories = [],
        private ?NamingStrategy $namingStrategy = null,
        /** @var array<class-string<AbstractPlatform>, ClassMetadata::GENERATOR_TYPE_*> */
        private array $identityGenerationPreferences = [],
        private ?EntityListenerResolver $entityListenerResolver = null,
        private ?RepositoryFactory $repositoryFactory = null,
        /** @var array<array-key, callable(mixed):bool> */
        private array $schemaAssetFilters = [],
        /** @var list<class-string> */
        private array $schemaIgnoreClasses = [],
        private ?CacheItemPoolInterface $metadataCache = null,
        private ?CacheItemPoolInterface $queryCache = null,
        private ?CacheItemPoolInterface $resultCache = null,
        /** @var array<string, class-string<FunctionNode>|callable(string):FunctionNode> */
        private array $customDatetimeFunctions = [],
        /** @var array<string, class-string<FunctionNode>|callable(string):FunctionNode> */
        private array $customNumericFunctions = [],
        /** @var array<string, class-string<FunctionNode>|callable(string):FunctionNode> */
        private array $customStringFunctions = [],
        /** @var array<string, class-string<SQLFilter>> */
        private array $filters = [],
        /** @var array<array-key, string> */
        private array $enabledFilters = [],
        private ?string $proxyDir = null,
        private ?string $proxyNamespace = null,
        /** @var (ProxyFactory::AUTOGENERATE_*)|null */
        private ?int $autoGenerateProxyClasses = null,
        /** @var array<string, EventSubscriber> */
        private array $eventSubscribers = [],
        private ?LoggerInterface $logger = null,
        /** @var array<array-key, callable(Configuration):void> */
        private array $configurators = [],
        /** @var array<array-key, callable(EntityManagerInterface):void> */
        private array $entityManagerConfigurators = [],
    ) {
    }

    public function getName(): EntityManagerNameInterface
    {
        return $this->name;
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    /**
     * @inheritDoc
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @inheritDoc
     */
    public function getMiddlewares(): array
    {
        $middlewares = $this->middlewares;

        if ($this->logger !== null) {
            $middlewares[] = new LoggingMiddleware($this->logger);
        }

        return $middlewares;
    }

    /**
     * @inheritDoc
     */
    public function getEntityDirectories(): array
    {
        return $this->entityDirectories;
    }

    public function getNamingStrategy(): NamingStrategy
    {
        return $this->namingStrategy ?? new UnderscoreNamingStrategy();
    }

    /**
     * @inheritDoc
     */
    public function getIdentityGenerationPreferences(): array
    {
        return $this->identityGenerationPreferences;
    }

    public function getEntityListenerResolver(): EntityListenerResolver
    {
        return $this->entityListenerResolver ?? new DefaultEntityListenerResolver();
    }

    public function getRepositoryFactory(): RepositoryFactory
    {
        return $this->repositoryFactory ?? new DefaultRepositoryFactory();
    }

    /**
     * @inheritDoc
     */
    public function getSchemaAssetsFilter(): callable
    {
        return function (mixed $asset): bool {
            foreach ($this->schemaAssetFilters as $filter) {
                if ($filter($asset) === false) {
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * @inheritDoc
     */
    public function getSchemaIgnoreClasses(): array
    {
        return $this->schemaIgnoreClasses;
    }

    public function getMetadataCache(): CacheItemPoolInterface
    {
        return $this->metadataCache ?? new ArrayAdapter();
    }

    public function getQueryCache(): CacheItemPoolInterface
    {
        return $this->queryCache ?? new ArrayAdapter();
    }

    public function getResultCache(): CacheItemPoolInterface
    {
        return $this->resultCache ?? new ArrayAdapter();
    }

    /**
     * @inheritDoc
     */
    public function getCustomDatetimeFunctions(): array
    {
        return $this->customDatetimeFunctions;
    }

    /**
     * @inheritDoc
     */
    public function getCustomNumericFunctions(): array
    {
        return $this->customNumericFunctions;
    }

    /**
     * @inheritDoc
     */
    public function getCustomStringFunctions(): array
    {
        return $this->customStringFunctions;
    }

    /**
     * @inheritDoc
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @inheritDoc
     */
    public function getEnabledFilters(): array
    {
        return $this->enabledFilters;
    }

    public function getProxyDir(): string
    {
        return $this->proxyDir ?? \sys_get_temp_dir() . '/doctrine-proxies';
    }

    public function getProxyNamespace(): string
    {
        return $this->proxyNamespace ?? 'DoctrineProxies';
    }

    /**
     * @inheritDoc
     */
    public function getAutoGenerateProxyClasses(): int
    {
        return $this->autoGenerateProxyClasses ?? ProxyFactory::AUTOGENERATE_ALWAYS;
    }

    /**
     * @inheritDoc
     */
    public function getEventSubscribers(): array
    {
        return $this->eventSubscribers;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @inheritDoc
     */
    public function getConfigurators(): array
    {
        return $this->configurators;
    }

    /**
     * @inheritDoc
     */
    public function getEntityManagerConfigurators(): array
    {
        return $this->entityManagerConfigurators;
    }
}
