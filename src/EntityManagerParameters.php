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

use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Psr\Cache\CacheItemPoolInterface;
use SensitiveParameter;

final readonly class EntityManagerParameters implements EntityManagerParametersInterface
{
    public function __construct(
        private EntityManagerNameInterface $name,
        #[SensitiveParameter]
        private string $dsn,
        /** @var array<array-key, string> */
        private array $entityDirectories,
        private string $proxyDirectory,
        private string $proxyNamespace,
        /** @var ProxyFactory::AUTOGENERATE_* */
        private int $proxyAutogenerate,
        private CacheItemPoolInterface $metadataCache,
        private CacheItemPoolInterface $queryCache,
        private CacheItemPoolInterface $resultCache,
        private NamingStrategy $namingStrategy,
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

    public function getProxyDirectory(): string
    {
        return $this->proxyDirectory;
    }

    public function getProxyNamespace(): string
    {
        return $this->proxyNamespace;
    }

    /**
     * @inheritDoc
     */
    public function getProxyAutogenerate(): int
    {
        return $this->proxyAutogenerate;
    }

    public function getMetadataCache(): CacheItemPoolInterface
    {
        return $this->metadataCache;
    }

    public function getQueryCache(): CacheItemPoolInterface
    {
        return $this->queryCache;
    }

    public function getResultCache(): CacheItemPoolInterface
    {
        return $this->resultCache;
    }

    public function getNamingStrategy(): NamingStrategy
    {
        return $this->namingStrategy;
    }
}
