<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\EventSubscriber\EntityValidationOnPreSave;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function DI\add;
use function DI\create;
use function DI\get;

return [
    EntityValidationOnPreSave::class => create()
        ->constructor(
            validator: get(ValidatorInterface::class),
        ),

    'doctrine.entity_manager_parameters.*.event_subscribers' => add([
        get(EntityValidationOnPreSave::class),
    ]),
];
