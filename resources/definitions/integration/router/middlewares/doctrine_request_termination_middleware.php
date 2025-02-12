<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Router\Middleware\DoctrineRequestTerminationMiddleware;

use function DI\add;
use function DI\create;
use function DI\get;

return [
    'router.doctrine_request_termination_middleware.flushable_entity_manager_names' => [],
    'router.doctrine_request_termination_middleware.clearable_entity_manager_names' => [],

    'router.middlewares' => add([
        create(DoctrineRequestTerminationMiddleware::class)
            ->constructor(
                entityManagerRegistry: get(EntityManagerRegistryInterface::class),
                flushableEntityManagerNames: get('router.doctrine_request_termination_middleware.flushable_entity_manager_names'),
                clearableEntityManagerNames: get('router.doctrine_request_termination_middleware.clearable_entity_manager_names'),
            ),
    ]),
];
