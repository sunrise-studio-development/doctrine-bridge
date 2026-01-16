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

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
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
        /** @var array<array-key, string> */
        private array $entityDirectories = [],
        private ?NamingStrategy $namingStrategy = null,
        /** @var array<array-key, callable(mixed):bool> */
        private array $schemaAssetFilters = [],
        /** @var list<class-string> */
        private array $schemaIgnoreClasses = [],
        private ?string $proxyDirectory = null,
        private ?string $proxyNamespace = null,
        /** @var (ProxyFactory::AUTOGENERATE_*)|null */
        private ?int $proxyAutogenerate = null,
        private ?CacheItemPoolInterface $metadataCache = null,
        private ?CacheItemPoolInterface $queryCache = null,
        private ?CacheItemPoolInterface $resultCache = null,
        /** @var array<string, class-string<FunctionNode>|callable(string):FunctionNode> */
        private array $customDatetimeFunctions = [],
        /** @var array<string, class-string<FunctionNode>|callable(string):FunctionNode> */
        private array $customNumericFunctions = [],
        /** @var array<string, class-string<FunctionNode>|callable(string):FunctionNode> */
        private array $customStringFunctions = [],
        /** @var array<array-key, Middleware> */
        private array $middlewares = [],
        /** @var array<string, EventSubscriber> */
        private array $eventSubscribers = [],
        /** @var array<array-key, callable(Configuration):void> */
        private array $configurators = [],
        /** @var array<string, class-string<Type>> */
        private array $types = [],
        private ?LoggerInterface $logger = null,
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

    public function getProxyDirectory(): string
    {
        return $this->proxyDirectory ?? \sys_get_temp_dir() . '/doctrine-proxies';
    }

    public function getProxyNamespace(): string
    {
        return $this->proxyNamespace ?? 'DoctrineProxies';
    }

    /**
     * @inheritDoc
     */
    public function getProxyAutogenerate(): int
    {
        return $this->proxyAutogenerate ?? ProxyFactory::AUTOGENERATE_ALWAYS;
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
    public function getEventSubscribers(): array
    {
        return $this->eventSubscribers;
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
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @inheritDoc
     */
    public function getEntityManagerConfigurators(): array
    {
        return $this->entityManagerConfigurators;
    }
}
