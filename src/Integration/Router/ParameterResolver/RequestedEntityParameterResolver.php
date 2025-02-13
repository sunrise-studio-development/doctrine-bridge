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

use Doctrine\ORM\Mapping\MappingException;
use Generator;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Router\Annotation\RequestedEntity;
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

        /** @var list<ReflectionAttribute<RequestedEntity>> $annotations */
        $annotations = $parameter->getAttributes(RequestedEntity::class);
        if ($annotations === []) {
            return;
        }

        $type = $parameter->getType();
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            throw new InvalidArgumentException(sprintf(
                'To use the #[RequestEntity] annotation, the parameter "%s" must be typed with an entity.',
                ParameterResolverChain::stringifyParameter($parameter),
            ));
        }

        $entityName = $type->getName();
        if (!class_exists($entityName)) {
            throw new InvalidArgumentException(sprintf(
                'The parameter "%s" cannot be resolved because it is typed with a non-existent entity.',
                ParameterResolverChain::stringifyParameter($parameter),
            ));
        }

        $processParams = $annotations[0]->newInstance();

        $em = $this->entityManagerRegistry->getEntityManager($processParams->em ?? $this->defaultEntityManagerName);
        $field = $processParams->field ?? $em->getClassMetadata($entityName)->getSingleIdentifierFieldName();
        $value = ServerRequest::create($context)->getRoute()->getAttribute($processParams->variable ?? $field);
        $entity = $em->getRepository($entityName)->findOneBy([$field => $value, ...$processParams->criteria]);

        if ($entity === null) {
            return $parameter->allowsNull() ? yield null : throw HttpExceptionFactory::resourceNotFound();
        }

        yield $entity;
    }

    public function getWeight(): int
    {
        return -10;
    }
}
