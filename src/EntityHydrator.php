<?php declare(strict_types=1);

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2020, Anatoly Fenric
 * @license https://github.com/sunrise-php/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-php/doctrine-bridge
 */

namespace Sunrise\Bridge\Doctrine;

/**
 * Import classes
 */
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\String\Inflector\EnglishInflector;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Import functions
 */
use function array_key_exists;
use function class_exists;
use function get_class;
use function in_array;
use function is_int;
use function is_object;
use function is_string;
use function sprintf;
use function str_replace;
use function strpos;
use function ucfirst;
use function ucwords;

/**
 * EntityHydrator
 */
final class EntityHydrator
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Hydrates the given entity with the given data
     *
     * @param object|string $entity
     * @param array<string, mixed> $data
     *
     * @return object
     *
     * @throws InvalidArgumentException
     */
    public function hydrate($entity, array $data) : object
    {
        $entity = $this->initializeEntity($entity);

        $metadata = $this->entityManager->getClassMetadata(get_class($entity));

        $this->hydrateFields($metadata, $entity, $data);
        $this->hydrateAssociations($metadata, $entity, $data);

        return $entity;
    }

    /**
     * Initializes the given entity
     *
     * @param object|string $entity
     *
     * @return object
     *
     * @throws InvalidArgumentException
     */
    private function initializeEntity($entity) : object
    {
        if (is_object($entity)) {
            return $entity;
        }

        if (!is_string($entity) || !class_exists($entity)) {
            throw new InvalidArgumentException(sprintf(
                'The method %s::hydrate() expects an object or name of an existing class.',
                __CLASS__
            ));
        }

        $class = new ReflectionClass($entity);
        $constructor = $class->getConstructor();
        if (isset($constructor) && 0 < $constructor->getNumberOfRequiredParameters()) {
            throw new InvalidArgumentException(sprintf(
                'The entity %s cannot be hydrated because its constructor has required parameters.',
                $class->getName()
            ));
        }

        return $class->newInstance();
    }

    /**
     * Hydrates fields of the given entity with the given data
     *
     * @param ClassMetadataInfo $metadata
     * @param object $entity
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function hydrateFields(ClassMetadataInfo $metadata, object $entity, array $data) : void
    {
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();

        foreach ($metadata->fieldMappings as $mapping) {
            // the field isn't represented in the data...
            if (!array_key_exists($mapping['fieldName'], $data)) {
                continue;
            }

            // identifiers can't be hydrated...
            if ($metadata->isIdentifier($mapping['fieldName'])) {
                continue;
            }

            $setter = $this->getFieldSetter($metadata->getReflectionClass(), $mapping['fieldName']);
            if (null === $setter) {
                continue;
            }

            $type = Type::getType($mapping['type']);

            try {
                $value = $type->convertToPHPValue($data[$mapping['fieldName']], $platform);
            } catch (ConversionException $e) {
                // data validation goes beyond the hydrator...
                continue;
            }

            // the seter doesn't expect null...
            if (null === $value && !$setter->getParameters()[0]->allowsNull()) {
                continue;
            }

            $setter->invoke($entity, $value);
        }
    }

    /**
     * Hydrates associations of the given entity with the given data
     *
     * @param ClassMetadataInfo $metadata
     * @param object $entity
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function hydrateAssociations(ClassMetadataInfo $metadata, object $entity, array $data) : void
    {
        foreach ($metadata->associationMappings as $mapping) {
            // the field isn't represented in the data...
            if (!array_key_exists($mapping['fieldName'], $data)) {
                continue;
            }

            if (in_array($mapping['type'], [ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE])) {
                $this->hydrateFieldWithToOneAssociation(
                    $metadata,
                    $entity,
                    $mapping['fieldName'],
                    $mapping['targetEntity'],
                    $data[$mapping['fieldName']]
                );

                continue;
            }

            if (in_array($mapping['type'], [ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY])) {
                $this->hydrateFieldWithToManyAssociation(
                    $metadata,
                    $entity,
                    $mapping['fieldName'],
                    $mapping['targetEntity'],
                    $data[$mapping['fieldName']]
                );

                continue;
            }
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     * @param object $entity
     * @param string $fieldName
     * @param string $targetEntity
     * @param mixed $value
     *
     * @return void
     */
    private function hydrateFieldWithToOneAssociation(
        ClassMetadataInfo $metadata,
        object $entity,
        string $fieldName,
        string $targetEntity,
        $value
    ) : void {
        $setter = $this->getFieldSetter($metadata->getReflectionClass(), $fieldName);
        if (null === $setter) {
            return;
        }

        if (null === $value) {
            if ($setter->getParameters()[0]->allowsNull()) {
                $setter->invoke($entity, null);
            }

            return;
        }

        $this->setAssociationToField($entity, $setter, $targetEntity, $value);
    }

    /**
     * @param ClassMetadataInfo $metadata
     * @param object $entity
     * @param string $fieldName
     * @param string $targetEntity
     * @param mixed $value
     *
     * @return void
     */
    private function hydrateFieldWithToManyAssociation(
        ClassMetadataInfo $metadata,
        object $entity,
        string $fieldName,
        string $targetEntity,
        $value
    ) : void {
        // adders should not accept null...
        if (null === $value) {
            return;
        }

        $adder = $this->getFieldAdder($metadata->getReflectionClass(), $fieldName);
        if (null === $adder) {
            return;
        }

        $isSuccess = $this->setAssociationToField($entity, $adder, $targetEntity, $value);
        if (true === $isSuccess) {
            return;
        }

        if (Helper::isList($value)) {
            foreach ($value as $item) {
                $this->setAssociationToField($entity, $adder, $targetEntity, $item);
            }
        }
    }

    /**
     * @param object $entity
     * @param ReflectionMethod $setter
     * @param string $targetEntity
     * @param mixed $value
     *
     * @return bool
     */
    private function setAssociationToField(
        object $entity,
        ReflectionMethod $setter,
        string $targetEntity,
        $value
    ) : bool {
        // such value can only be an identifier...
        if (is_int($value) || is_string($value)) {
            $object = $this->entityManager->getReference($targetEntity, $value);
            if (isset($object)) {
                $setter->invoke($entity, $object);
            }

            // it is not a confirmation of setting,
            // it is an indication of type acceptance...
            return true;
        }

        if (Helper::isDict($value)) {
            $object = $this->hydrate($targetEntity, $value);

            $setter->invoke($entity, $object);

            return true;
        }

        return false;
    }

    /**
     * @param ReflectionClass $class
     * @param string $fieldName
     *
     * @return ReflectionMethod|null
     */
    private function getFieldAdder(ReflectionClass $class, string $fieldName) : ?ReflectionMethod
    {
        $camelizedFieldName = $this->camelizeFieldName($fieldName);

        // Sometimes it's not possible to determine a unique singular/plural form for the given word.
        // In those cases, the methods return an array with all the possible forms.
        $singularFieldNames = (array) (new EnglishInflector)->singularize($camelizedFieldName);

        foreach ($singularFieldNames as $singularFieldName) {
            $adderName = 'add' . $singularFieldName;
            if (!$class->hasMethod($adderName)) {
                continue;
            }

            $adder = $class->getMethod($adderName);
            if (!$adder->isPublic() || $adder->isStatic() || 0 === $adder->getNumberOfParameters()) {
                break;
            }

            return $adder;
        }

        return null;
    }

    /**
     * @param ReflectionClass $class
     * @param string $fieldName
     *
     * @return ReflectionMethod|null
     */
    private function getFieldSetter(ReflectionClass $class, string $fieldName) : ?ReflectionMethod
    {
        $camelizedFieldName = $this->camelizeFieldName($fieldName);

        $setterName = 'set' . $camelizedFieldName;
        if (!$class->hasMethod($setterName)) {
            return null;
        }

        $setter = $class->getMethod($setterName);
        if (!$setter->isPublic() || $setter->isStatic() || 0 === $setter->getNumberOfParameters()) {
            return null;
        }

        return $setter;
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    private function camelizeFieldName(string $fieldName) : string
    {
        if (false === strpos($fieldName, '_')) {
            return ucfirst($fieldName);
        }

        $fieldName = str_replace('_', ' ', $fieldName);
        $fieldName = ucwords($fieldName);
        $fieldName = str_replace(' ', '', $fieldName);

        return $fieldName;
    }
}
