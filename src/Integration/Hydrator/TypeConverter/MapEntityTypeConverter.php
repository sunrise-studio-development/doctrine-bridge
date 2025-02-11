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

namespace Sunrise\Bridge\Doctrine\Integration\Hydrator\TypeConverter;

use Doctrine\ORM\Mapping\MappingException;
use Generator;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Hydrator\Annotation\MapEntity;
use Sunrise\Hydrator\AnnotationReaderAwareInterface;
use Sunrise\Hydrator\AnnotationReaderInterface;
use Sunrise\Hydrator\Annotation\Subtype;
use Sunrise\Hydrator\Exception\InvalidObjectException;
use Sunrise\Hydrator\Exception\InvalidValueException;
use Sunrise\Hydrator\HydratorAwareInterface;
use Sunrise\Hydrator\HydratorInterface;
use Sunrise\Hydrator\Type;
use Sunrise\Hydrator\TypeConverterInterface;

use function class_exists;
use function is_string;
use function trim;

final class MapEntityTypeConverter implements
    TypeConverterInterface,
    HydratorAwareInterface,
    AnnotationReaderAwareInterface
{
    public const string ERROR_CODE = '0a430d9b-b266-4618-b4c8-e1221343b900';
    public const string ERROR_MESSAGE = 'The entity was not found.';

    private HydratorInterface $hydrator;
    private AnnotationReaderInterface $annotationReader;

    public function __construct(
        private readonly EntityManagerRegistryInterface $entityManagerRegistry,
        private readonly EntityManagerNameInterface $defaultEntityManagerName,
    ) {
    }

    public function setHydrator(HydratorInterface $hydrator): void
    {
        $this->hydrator = $hydrator;
    }

    public function setAnnotationReader(AnnotationReaderInterface $annotationReader): void
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * @inheritDoc
     *
     * @throws MappingException
     */
    public function castValue($value, Type $type, array $path, array $context): Generator
    {
        $holder = $type->getHolder();

        /** @var MapEntity|null $mapEntity */
        $mapEntity = $this->annotationReader->getAnnotations(MapEntity::class, $holder)->current();
        if ($mapEntity === null) {
            return;
        }

        $entityName = $type->getName();
        if (!class_exists($entityName)) {
            throw InvalidObjectException::unsupportedType($type);
        }

        /** @var Subtype|null $subtype */
        $subtype = $this->annotationReader->getAnnotations(Subtype::class, $holder)->current();
        if ($subtype !== null) {
            $castType = new Type(null, $subtype->name, $subtype->allowsNull);
            $value = $this->hydrator->castValue($value, $castType, $path, $context);
        }

        // As part of the support for HTML forms and other untyped data sources,
        // empty strings should not be used to resolve entities;
        // instead, they should be considered as NULL.
        if (is_string($value) && ($value = trim($value)) === '') {
            return $type->allowsNull() ? yield null : throw InvalidValueException::mustNotBeEmpty($path);
        }

        $entityManagerName = $mapEntity->em ?? $this->defaultEntityManagerName;
        $entityManager = $this->entityManagerRegistry->getEntityManager($entityManagerName);
        $entityMetadata = $entityManager->getClassMetadata($entityName);
        $entityRepository = $entityManager->getRepository($entityName);

        $field = $mapEntity->field ?? $entityMetadata->getSingleIdentifierFieldName();
        /** @var array<string, mixed> $criteria */
        $criteria = [$field => $value, ...$mapEntity->criteria];

        $entity = $entityRepository->findOneBy($criteria);

        if ($entity === null) {
            throw new InvalidValueException(
                message: self::ERROR_MESSAGE,
                errorCode: self::ERROR_CODE,
                propertyPath: $path,
                messageTemplate: self::ERROR_MESSAGE,
                messagePlaceholders: [],
            );
        }

        yield $entity;
    }

    /**
     * @inheritDoc
     */
    public function getWeight(): int
    {
        return -10;
    }
}
