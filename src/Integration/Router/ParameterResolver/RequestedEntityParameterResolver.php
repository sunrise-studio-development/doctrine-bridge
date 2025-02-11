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

namespace Sunrise\Bridge\Doctrine\Integration\Router\ParameterResolver;

use Generator;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Router\Annotation\RequestedEntity;
use Sunrise\Bridge\Doctrine\Integration\Router\Mapping\RouteVariable;
use Sunrise\Http\Router\Exception\HttpExceptionFactory;
use Sunrise\Http\Router\ParameterResolverChain;
use Sunrise\Http\Router\ParameterResolverInterface;
use Sunrise\Http\Router\ServerRequest;

use function class_exists;
use function sprintf;

final readonly class RequestedEntityParameterResolver implements ParameterResolverInterface
{
    public function __construct(
        private EntityManagerRegistryInterface $entityManagerRegistry,
        private EntityManagerNameInterface $defaultEntityManagerName,
    ) {
    }

    public function resolveParameter(ReflectionParameter $parameter, mixed $context): Generator
    {
        if (! $context instanceof ServerRequestInterface) {
            return;
        }

        /** @var list<ReflectionAttribute<RequestedEntity>> $annotations */
        $annotations = $parameter->getAttributes(RequestedEntity::class);
        if ($annotations === []) {
            return;
        }

        $type = $parameter->getType();
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            throw new InvalidArgumentException(sprintf(
                'To use the #[RequestEntity] annotation, the parameter %s must be typed with an entity.',
                ParameterResolverChain::stringifyParameter($parameter),
            ));
        }

        $entityName = $type->getName();
        if (!class_exists($entityName)) {
            throw new InvalidArgumentException(sprintf(
                'The parameter %s cannot be resolved because it is typed with a non-existent entity.',
                ParameterResolverChain::stringifyParameter($parameter),
            ));
        }

        $route = ServerRequest::create($context)->getRoute();
        $processParams = $annotations[0]->newInstance();

        $entityManagerName = $processParams->em ?? $this->defaultEntityManagerName;
        $entityManager = $this->entityManagerRegistry->getEntityManager($entityManagerName);
        $entityMetadata = $entityManager->getClassMetadata($entityName);
        $entityRepository = $entityManager->getRepository($entityName);

        /** @var array<string, mixed> $criteria */
        $criteria = [];
        $isRouteVariableUsed = false;

        foreach ($processParams->criteria as $field => $value) {
            if (
                !$entityMetadata->hasField($field) &&
                !$entityMetadata->hasAssociation($field)
            ) {
                throw new InvalidArgumentException(sprintf(
                    'The parameter %s cannot be resolved because the entity field %s is not mapped by Doctrine.',
                    ParameterResolverChain::stringifyParameter($parameter),
                    $field,
                ));
            }

            if ($value instanceof RouteVariable) {
                $attribute = $value->name ?? $field;
                if ($route->hasAttribute($attribute)) {
                    $criteria[$field] = $route->getAttribute($attribute);
                }

                $isRouteVariableUsed = true;
                continue;
            }

            $criteria[$field] = $value;
        }

        if ($isRouteVariableUsed === false) {
            foreach ($route->getAttributes() as $attribute => $value) {
                if (
                    $entityMetadata->hasField($attribute) ||
                    $entityMetadata->hasAssociation($attribute)
                ) {
                    $criteria[$attribute] = $value;
                    $isRouteVariableUsed = true;
                }
            }
        }

        if ($isRouteVariableUsed === false) {
            throw new InvalidArgumentException(sprintf(
                'The parameter %s cannot be resolved because the search criteria could not be formed.',
                ParameterResolverChain::stringifyParameter($parameter),
            ));
        }

        $entity = $entityRepository->findOneBy($criteria);

        if ($entity === null) {
            return $parameter->allowsNull() ? yield null : throw HttpExceptionFactory::resourceNotFound();
        }

        yield $entity;
    }

    public function getWeight(): int
    {
        return 0;
    }
}
