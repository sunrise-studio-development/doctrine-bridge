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
use Psr\Log\LoggerInterface;

interface EntityManagerParametersInterface
{
    public function getName(): EntityManagerNameInterface;

    public function getDsn(): string;

    /**
     * @return array<array-key, string>
     */
    public function getEntityDirectories(): array;

    public function getProxyDirectory(): string;

    public function getProxyNamespace(): string;

    /**
     * @return ProxyFactory::AUTOGENERATE_*
     */
    public function getProxyAutogenerate(): int;

    public function getMetadataCache(): CacheItemPoolInterface;

    public function getQueryCache(): CacheItemPoolInterface;

    public function getResultCache(): CacheItemPoolInterface;

    public function getNamingStrategy(): NamingStrategy;

    public function getLogger(): ?LoggerInterface;
}
