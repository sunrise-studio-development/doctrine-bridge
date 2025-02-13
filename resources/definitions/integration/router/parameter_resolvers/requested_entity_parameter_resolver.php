<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Router\ParameterResolver\RequestedEntityParameterResolver;

use function DI\add;
use function DI\create;
use function DI\get;

return [
    'router.requested_entity_parameter_resolver.default_entity_manager_name' => null,

    'router.parameter_resolvers' => add([
        create(RequestedEntityParameterResolver::class)
            ->constructor(
                entityManagerRegistry: get(EntityManagerRegistryInterface::class),
                defaultEntityManagerName: get('router.requested_entity_parameter_resolver.default_entity_manager_name'),
            ),
    ]),
];
