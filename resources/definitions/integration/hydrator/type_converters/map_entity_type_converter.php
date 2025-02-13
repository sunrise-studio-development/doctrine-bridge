<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Hydrator\TypeConverter\MapEntityTypeConverter;

use function DI\add;
use function DI\create;
use function DI\get;

return [
    'hydrator.map_entity_type_converter.default_entity_manager_name' => null,

    'hydrator.type_converters' => add([
        create(MapEntityTypeConverter::class)
            ->constructor(
                entityManagerRegistry: get(EntityManagerRegistryInterface::class),
                defaultEntityManagerName: get('hydrator.map_entity_type_converter.default_entity_manager_name'),
            ),
    ]),
];
