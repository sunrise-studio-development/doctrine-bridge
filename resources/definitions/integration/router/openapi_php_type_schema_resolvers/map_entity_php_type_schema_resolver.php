<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\Integration\Router\OpenApi\MapEntityPhpTypeSchemaResolver;

use function DI\add;
use function DI\create;

return [
    'router.openapi.php_type_schema_resolvers' => add([
        create(MapEntityPhpTypeSchemaResolver::class),
    ]),
];
