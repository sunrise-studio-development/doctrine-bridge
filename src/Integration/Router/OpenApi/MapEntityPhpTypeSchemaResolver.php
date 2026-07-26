<?php

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatolii Nekhai <afenric@gmail.com>
 * @copyright Copyright (c) 2025, Anatolii Nekhai
 * @license https://github.com/sunrise-studio-development/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-studio-development/doctrine-bridge
 */

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Integration\Router\OpenApi;

use ReflectionAttribute;
use ReflectionProperty;
use Reflector;
use Sunrise\Bridge\Doctrine\Integration\Hydrator\Annotation\MapEntity;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Http\Router\OpenApi\TypeInterface;
use Sunrise\Hydrator\Annotation\Subtype;

/**
 * @since 3.3.0
 */
final readonly class MapEntityPhpTypeSchemaResolver implements
    OpenApiPhpTypeSchemaResolverInterface,
    OpenApiPhpTypeSchemaResolverManagerAwareInterface
{
    private OpenApiPhpTypeSchemaResolverManagerInterface $openApiPhpTypeSchemaResolverManager;

    public function setOpenApiPhpTypeSchemaResolverManager(
        OpenApiPhpTypeSchemaResolverManagerInterface $openApiPhpTypeSchemaResolverManager,
    ): void {
        $this->openApiPhpTypeSchemaResolverManager = $openApiPhpTypeSchemaResolverManager;
    }

    public function supportsPhpType(TypeInterface $phpType, Reflector $phpTypeHolder): bool
    {
        return $phpTypeHolder instanceof ReflectionProperty
            && $phpTypeHolder->getAttributes(MapEntity::class) !== []
            && $phpTypeHolder->getAttributes(Subtype::class, ReflectionAttribute::IS_INSTANCEOF) !== [];
    }

    /**
     * @inheritDoc
     */
    public function resolvePhpTypeSchema(TypeInterface $phpType, Reflector $phpTypeHolder): array
    {
        if (! $phpTypeHolder instanceof ReflectionProperty) {
            return [];
        }

        /** @var Subtype|null $valueType */
        $valueType = ($phpTypeHolder->getAttributes(
            Subtype::class,
            ReflectionAttribute::IS_INSTANCEOF,
        )[0] ?? null)?->newInstance();

        if ($valueType === null) {
            return [];
        }

        return $this->openApiPhpTypeSchemaResolverManager->resolvePhpTypeSchema(
            new Type($valueType->name, $valueType->allowsNull),
            $phpTypeHolder,
        );
    }

    public function getWeight(): int
    {
        return -10;
    }
}
