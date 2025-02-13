<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueEntityValidator;

use function DI\create;
use function DI\get;

return [
    'validator.unique_entity.default_entity_manager_name' => null,
    'validator.unique_entity.logger' => null,

    UniqueEntityValidator::class => create()
        ->constructor(
            entityManagerRegistry: get(EntityManagerRegistryInterface::class),
            defaultEntityManagerName: get('validator.unique_entity.default_entity_manager_name'),
            logger: get('validator.unique_entity.logger'),
        ),
];
