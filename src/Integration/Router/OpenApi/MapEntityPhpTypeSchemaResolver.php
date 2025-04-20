<?php

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Nekhay <afenric@gmail.com>
 * @copyright Copyright (c) 2025, Anatoly Nekhay
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

    public function supportsPhpType(Type $phpType, Reflector $phpTypeHolder): bool
    {
        return $phpTypeHolder instanceof ReflectionProperty
            && $phpTypeHolder->getAttributes(MapEntity::class) !== []
            && $phpTypeHolder->getAttributes(Subtype::class) !== [];
    }

    /**
     * @inheritDoc
     */
    public function resolvePhpTypeSchema(Type $phpType, Reflector $phpTypeHolder): array
    {
        if (! $phpTypeHolder instanceof ReflectionProperty) {
            return [];
        }

        /** @var list<ReflectionAttribute<Subtype>> $annotations */
        $annotations = $phpTypeHolder->getAttributes(Subtype::class);
        if ($annotations === []) {
            return [];
        }

        $annotation = $annotations[0]->newInstance();

        return $this->openApiPhpTypeSchemaResolverManager->resolvePhpTypeSchema(
            new Type($annotation->name, $annotation->allowsNull),
            $phpTypeHolder,
        );
    }

    public function getWeight(): int
    {
        return -10;
    }
}
