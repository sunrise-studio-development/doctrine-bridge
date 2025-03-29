<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Sunrise\Bridge\Doctrine\EventSubscriber\EntityValidationOnPreSave;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function DI\create;
use function DI\decorate;
use function DI\get;

return [
    EntityValidationOnPreSave::class => create()
        ->constructor(
            validator: get(ValidatorInterface::class),
        ),

    'doctrine.entity_manager_parameters.*.event_subscribers' => decorate(
        static function (array $previous, ContainerInterface $container) {
            $previous[] = $container->get(EntityValidationOnPreSave::class);
            return $previous;
        }
    ),
];
