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

use a;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
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

    public function getNamingStrategy(): NamingStrategy;

    /**
     * @return callable(mixed):bool
     *
     * @since 3.5.0
     */
    public function getSchemaAssetsFilter(): callable;

    /**
     * @return list<class-string>
     *
     * @since 3.5.0
     */
    public function getSchemaIgnoreClasses(): array;

    public function getProxyDirectory(): string;

    public function getProxyNamespace(): string;

    /**
     * @return ProxyFactory::AUTOGENERATE_*
     */
    public function getProxyAutogenerate(): int;

    public function getMetadataCache(): CacheItemPoolInterface;

    public function getQueryCache(): CacheItemPoolInterface;

    public function getResultCache(): CacheItemPoolInterface;

    /**
     * @return array<string, class-string<FunctionNode>|callable(string):FunctionNode>
     *
     * @since 3.5.0
     */
    public function getCustomDatetimeFunctions(): array;

    /**
     * @return array<string, class-string<FunctionNode>|callable(string):FunctionNode>
     *
     * @since 3.5.0
     */
    public function getCustomNumericFunctions(): array;

    /**
     * @return array<string, class-string<FunctionNode>|callable(string):FunctionNode>
     *
     * @since 3.5.0
     */
    public function getCustomStringFunctions(): array;

    /**
     * @return array<array-key, Middleware>
     *
     * @since 3.3.0
     */
    public function getMiddlewares(): array;

    /**
     * @return array<array-key, EventSubscriber>
     *
     * @since 3.3.0
     */
    public function getEventSubscribers(): array;

    /**
     * @return array<array-key, callable(Configuration):void>
     *
     * @since 3.5.0
     */
    public function getConfigurators(): array;

    /**
     * @return array<string, class-string<Type>>
     *
     * @since 3.5.0
     */
    public function getTypes(): array;

    public function getLogger(): ?LoggerInterface;

    /**
     * @return array<array-key, callable(EntityManagerInterface):void>
     *
     * @since 3.7.0
     */
    public function getEntityManagerConfigurators(): array;
}
