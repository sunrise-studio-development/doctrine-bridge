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

    /**
     * @return array<array-key, EventSubscriber>
     *
     * @since 3.3.0
     */
    public function getEventSubscribers(): array;

    /**
     * @return array<array-key, Middleware>
     *
     * @since 3.3.0
     */
    public function getMiddlewares(): array;
}
