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

final readonly class MapEntityTypeConverter implements
    AnnotationReaderAwareInterface,
    HydratorAwareInterface,
    TypeConverterInterface
{
    public const ERROR_CODE = '0a430d9b-b266-4618-b4c8-e1221343b900';
    public const ERROR_MESSAGE = 'The entity was not found.';

    private AnnotationReaderInterface $annotationReader;
    private HydratorInterface $hydrator;

    public function __construct(
        private EntityManagerRegistryInterface $entityManagerRegistry,
        private ?EntityManagerNameInterface $defaultEntityManagerName = null,
    ) {
    }

    public function setAnnotationReader(AnnotationReaderInterface $annotationReader): void
    {
        $this->annotationReader = $annotationReader;
    }

    public function setHydrator(HydratorInterface $hydrator): void
    {
        $this->hydrator = $hydrator;
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

        // To support HTML forms and other untyped data sources,
        // empty strings should be treated as NULL rather than being resolved as entities.
        if (is_string($value) && ($value = trim($value)) === '') {
            return $type->allowsNull() ? yield : throw InvalidValueException::mustNotBeEmpty($path);
        }

        $em = $this->entityManagerRegistry->getEntityManager($mapEntity->em ?? $this->defaultEntityManagerName);
        $field = $mapEntity->field ?? $em->getClassMetadata($entityName)->getSingleIdentifierFieldName();
        $entity = $em->getRepository($entityName)->findOneBy([$field => $value, ...$mapEntity->criteria]);

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
