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

namespace Sunrise\Bridge\Doctrine\Integration\Router\ParameterResolver;

use Doctrine\ORM\Mapping\MappingException;
use Generator;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionNamedType;
use ReflectionParameter;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Router\Annotation\RequestedEntity;
use Sunrise\Http\Router\Exception\HttpExceptionFactory;
use Sunrise\Http\Router\ParameterResolverChain;
use Sunrise\Http\Router\ParameterResolverInterface;
use Sunrise\Http\Router\ServerRequest;

final readonly class RequestedEntityParameterResolver implements ParameterResolverInterface
{
    public function __construct(
        private EntityManagerRegistryInterface $entityManagerRegistry,
        private ?EntityManagerNameInterface $defaultEntityManagerName = null,
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws MappingException
     */
    public function resolveParameter(ReflectionParameter $parameter, mixed $context): Generator
    {
        if (! $context instanceof ServerRequestInterface) {
            return;
        }

        /** @var RequestedEntity|null $requestedEntity */
        $requestedEntity = ($parameter->getAttributes(RequestedEntity::class)[0] ?? null)?->newInstance();
        if ($requestedEntity === null) {
            return;
        }

        $type = $parameter->getType();
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            throw new InvalidArgumentException(\sprintf(
                'To use the #[RequestedEntity] annotation, the parameter "%s" must be typed with an entity.',
                ParameterResolverChain::stringifyParameter($parameter),
            ));
        }

        $entityName = $type->getName();
        if (!\class_exists($entityName)) {
            throw new InvalidArgumentException(\sprintf(
                'The parameter "%s" cannot be resolved because it is typed with a non-existent entity.',
                ParameterResolverChain::stringifyParameter($parameter),
            ));
        }

        $em = $this->entityManagerRegistry->getEntityManager($requestedEntity->em ?? $this->defaultEntityManagerName);
        $field = $requestedEntity->field ?? $em->getClassMetadata($entityName)->getSingleIdentifierFieldName();
        $value = ServerRequest::create($context)->getRoute()->getAttribute($requestedEntity->variable ?? $field);
        $entity = $em->getRepository($entityName)->findOneBy([$field => $value, ...$requestedEntity->criteria]);

        if ($entity === null && !$parameter->allowsNull()) {
            throw HttpExceptionFactory::resourceNotFound();
        }

        yield $entity;
    }

    public function getWeight(): int
    {
        return -10;
    }
}
