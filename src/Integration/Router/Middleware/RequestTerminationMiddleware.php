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

namespace Sunrise\Bridge\Doctrine\Integration\Router\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;

final readonly class RequestTerminationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EntityManagerRegistryInterface $entityManagerRegistry,
        /** @var array<array-key, EntityManagerNameInterface> */
        private array $flushableEntityManagerNames,
        /** @var array<array-key, EntityManagerNameInterface> */
        private array $clearableEntityManagerNames,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
            $this->flushEntityManagers();
            return $response;
        } finally {
            $this->clearEntityManagers();
        }
    }

    private function flushEntityManagers(): void
    {
        foreach ($this->flushableEntityManagerNames as $entityManagerName) {
            if ($this->entityManagerRegistry->hasEntityManager($entityManagerName)) {
                $this->entityManagerRegistry->getEntityManager($entityManagerName)->flush();
            }
        }
    }

    private function clearEntityManagers(): void
    {
        foreach ($this->clearableEntityManagerNames as $entityManagerName) {
            if ($this->entityManagerRegistry->hasEntityManager($entityManagerName)) {
                $this->entityManagerRegistry->getEntityManager($entityManagerName)->clear();
            }
        }
    }
}
