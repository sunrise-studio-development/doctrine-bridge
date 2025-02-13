<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Router\Middleware\RequestTerminationMiddleware;

use function DI\add;
use function DI\create;
use function DI\get;

return [
    'router.request_termination_middleware.flushable_entity_manager_names' => [],
    'router.request_termination_middleware.clearable_entity_manager_names' => [],

    'router.middlewares' => add([
        create(RequestTerminationMiddleware::class)
            ->constructor(
                entityManagerRegistry: get(EntityManagerRegistryInterface::class),
                flushableEntityManagerNames: get('router.request_termination_middleware.flushable_entity_manager_names'),
                clearableEntityManagerNames: get('router.request_termination_middleware.clearable_entity_manager_names'),
            ),
    ]),
];
