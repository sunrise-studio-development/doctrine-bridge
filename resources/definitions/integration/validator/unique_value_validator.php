<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueValueValidator;

use function DI\create;
use function DI\get;

return [
    'validator.unique_value.default_entity_manager_name' => null,
    'validator.unique_value.logger' => null,

    UniqueValueValidator::class => create()
        ->constructor(
            entityManagerRegistry: get(EntityManagerRegistryInterface::class),
            defaultEntityManagerName: get('validator.unique_value.default_entity_manager_name'),
            logger: get('validator.unique_value.logger'),
        ),
];
