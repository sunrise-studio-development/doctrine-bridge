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
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;
use Sunrise\Bridge\Doctrine\Annotation\Unhydrable;
use Symfony\Component\String\Inflector\EnglishInflector;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionType;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * Import functions
 */
use function array_key_exists;
use function class_exists;
use function date_create;
use function date_create_immutable;
use function date_diff;
use function explode;
use function filter_var;
use function get_class;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function sprintf;
use function str_replace;
use function strpos;
use function ucfirst;
use function ucwords;

/**
 * Import constants
 */
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;
use const PHP_MAJOR_VERSION;

/**
 * EntityHydrator
 */
final class EntityHydrator
{

    /**
     * 1970-01-01 - 2038-01-19
     *
     * @var string
     */
    private const STRING_DATE_INTERVAL_SEPARATOR = ' - ';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EnglishInflector
     *
     * @link https://symfony.com/doc/current/components/string.html#inflector
     */
    private $englishInflector;

    /**
     * @var SimpleAnnotationReader|null
     */
    private $annotationReader = null;

    /**
     * Constructor of the class
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->englishInflector = new EnglishInflector();

        if (PHP_MAJOR_VERSION < 8) {
            $this->useAnnotations();
        }
    }

    /**
     * Enables support for annotations
     *
     * @return void
     */
    public function useAnnotations() : void
    {
        if (isset($this->annotationReader)) {
            return;
        }

        $this->annotationReader = /** @scrutinizer ignore-deprecated */ new SimpleAnnotationReader();
        $this->annotationReader->addNamespace('Sunrise\Bridge\Doctrine\Annotation');
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

        try {
            $metadata = $this->entityManager->getClassMetadata(get_class($entity));
        } catch (MappingException $e) {
            throw new InvalidArgumentException($e->getMessage(), 0, $e);
        }

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
        if (isset($constructor) && $constructor->getNumberOfRequiredParameters() > 0) {
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
        $class = $metadata->getReflectionClass();

        foreach ($metadata->fieldMappings as $fieldName => $_) {
            if (!array_key_exists($fieldName, $data)) {
                continue;
            }

            if ($metadata->isIdentifier($fieldName)) {
                continue;
            }

            $field = $metadata->getReflectionProperty($fieldName);
            if (!$this->isHydrableField($field)) {
                continue;
            }

            $setter = $this->getFieldSetter($class, $field);
            if (null === $setter) {
                continue;
            }

            $value = $data[$fieldName];
            $parameter = $setter->getParameters()[0];
            if (null === $value) {
                if ($parameter->allowsNull()) {
                    $setter->invoke($entity, null);
                }

                continue;
            }

            if (!$parameter->hasType()) {
                $setter->invoke($entity, $value);

                continue;
            }

            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType) {
                $value = $this->typizeFieldValue($type, $value);
                if (isset($value)) {
                    $setter->invoke($entity, $value);
                }

                continue;
            }

            if ($type instanceof ReflectionUnionType) {
                foreach ($type->getTypes() as $oneOf) {
                    $result = $this->typizeFieldValue($oneOf, $value);
                    if (isset($result)) {
                        $setter->invoke($entity, $result);
                        break;
                    }
                }

                continue;
            }
        }
    }

    /**
     * Hydrates associations of the given entity with the given data
     *
     * Note that different hydration strategies will be applied,
     * depending on the type of association.
     *
     * @param ClassMetadataInfo $metadata
     * @param object $entity
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function hydrateAssociations(ClassMetadataInfo $metadata, object $entity, array $data) : void
    {
        $class = $metadata->getReflectionClass();

        foreach ($metadata->associationMappings as $fieldName => $mapping) {
            if (!array_key_exists($fieldName, $data)) {
                continue;
            }

            $field = $metadata->getReflectionProperty($fieldName);
            if (!$this->isHydrableField($field)) {
                continue;
            }

            $value = $data[$fieldName];

            if (in_array($mapping['type'], [ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE])) {
                $this->hydrateFieldWithOneAssociation($entity, $class, $field, $mapping['targetEntity'], $value);
                continue;
            }

            if (in_array($mapping['type'], [ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY])) {
                $this->hydrateFieldWithManyAssociations($entity, $class, $field, $mapping['targetEntity'], $value);
                continue;
            }
        }
    }

    /**
     * @param object $entity
     * @param ReflectionClass $class
     * @param ReflectionProperty $field
     * @param string $targetEntity
     * @param mixed $value
     *
     * @return void
     */
    private function hydrateFieldWithOneAssociation(
        object $entity,
        ReflectionClass $class,
        ReflectionProperty $field,
        string $targetEntity,
        $value
    ) : void {
        $setter = $this->getFieldSetter($class, $field);
        if (null === $setter) {
            return;
        }

        if (null === $value) {
            if ($setter->getParameters()[0]->allowsNull()) {
                $setter->invoke($entity, null);
            }

            return;
        }

        $object = $this->resolveAssociation($targetEntity, $value);
        if (isset($object)) {
            $setter->invoke($entity, $object);
        }
    }

    /**
     * @param object $entity
     * @param ReflectionClass $class
     * @param ReflectionProperty $field
     * @param string $targetEntity
     * @param mixed $value
     *
     * @return void
     */
    private function hydrateFieldWithManyAssociations(
        object $entity,
        ReflectionClass $class,
        ReflectionProperty $field,
        string $targetEntity,
        $value
    ) : void {
        // adders should not accept null...
        if (null === $value) {
            return;
        }

        $adder = $this->getFieldAdder($class, $field);
        if (null === $adder) {
            return;
        }

        $object = $this->resolveAssociation($targetEntity, $value);
        if (isset($object)) {
            $adder->invoke($entity, $object);
            return;
        }

        if (Helper::isList($value)) {
            foreach ($value as $item) {
                $object = $this->resolveAssociation($targetEntity, $item);
                if (isset($object)) {
                    $adder->invoke($entity, $object);
                }
            }
        }
    }

    /**
     * @param string $targetEntity
     * @param mixed $value
     *
     * @return object|null
     */
    private function resolveAssociation(string $targetEntity, $value) : ?object
    {
        if (is_int($value) || is_string($value)) {
            return $this->entityManager->getReference($targetEntity, $value);
        }

        if (Helper::isDict($value)) {
            return $this->hydrate($targetEntity, $value);
        }

        return null;
    }

    /**
     * Checks if the given field is hydrable
     *
     * @see Unhydrable
     *
     * @param ReflectionProperty $field
     *
     * @return bool
     */
    private function isHydrableField(ReflectionProperty $field) : bool
    {
        if (PHP_MAJOR_VERSION >= 8) {
            $unhydrable = $field->getAttributes(Unhydrable::class)[0] ?? null;
            if (isset($unhydrable)) {
                return false;
            }
        }

        if (isset($this->annotationReader)) {
            $unhydrable = $this->annotationReader->getPropertyAnnotation($field, Unhydrable::class);
            if (isset($unhydrable)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets an adder for the given field
     *
     * @param ReflectionClass $class
     * @param ReflectionProperty $field
     *
     * @return ReflectionMethod|null
     */
    private function getFieldAdder(ReflectionClass $class, ReflectionProperty $field) : ?ReflectionMethod
    {
        $singulars = (array) $this->englishInflector
            ->singularize($this->camelizeFieldName($field->name));

        foreach ($singulars as $singular) {
            $adderName = 'add' . $singular;
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
     * Gets a setter for the given field
     *
     * @param ReflectionClass $class
     * @param ReflectionProperty $field
     *
     * @return ReflectionMethod|null
     */
    private function getFieldSetter(ReflectionClass $class, ReflectionProperty $field) : ?ReflectionMethod
    {
        $setterName = 'set' . $this->camelizeFieldName($field->name);
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
     * Converts the given field name to UpperCamelCase
     *
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

    /**
     * Typizes the given value to the given type
     *
     * Returns null if the given value cannot be typized.
     *
     * @param ReflectionNamedType $type
     * @param bool|int|float|string|array|stdClass $value
     *
     * @return bool|int|float|string|array|stdClass|DateTime|DateTimeImmutable|DateInterval|null
     */
    private function typizeFieldValue(ReflectionNamedType $type, $value)
    {
        switch ($type->getName()) {
            case 'mixed':
                return $value;

            // if the value isn't boolean, then we will use filter_var, because it will give us the ability to specify
            // boolean values as strings. this behavior is great for html forms. details at:
            // https://github.com/php/php-src/blob/b7d90f09d4a1688f2692f2fa9067d0a07f78cc7d/ext/filter/logical_filters.c#L273
            case 'bool':
                return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            // it's senseless to convert the value type if it's not a number, so we will use filter_var to correct
            // converting the value type to int. also remember that string numbers must be between PHP_INT_MIN and
            // PHP_INT_MAX, otherwise the result will be null. this behavior is great for html forms. details at:
            // https://github.com/php/php-src/blob/b7d90f09d4a1688f2692f2fa9067d0a07f78cc7d/ext/filter/logical_filters.c#L197
            // https://github.com/php/php-src/blob/b7d90f09d4a1688f2692f2fa9067d0a07f78cc7d/ext/filter/logical_filters.c#L94
            case 'int':
                return is_int($value) ? $value : filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

            // it's senseless to convert the value type if it's not a number, so we will use filter_var to correct
            // converting the value type to float. this behavior is great for html forms. details at:
            // https://github.com/php/php-src/blob/b7d90f09d4a1688f2692f2fa9067d0a07f78cc7d/ext/filter/logical_filters.c#L342
            case 'float':
                return is_float($value) ? $value : filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

            case 'string':
                return is_string($value) ? $value : null;

            case 'array':
                return is_array($value) ? $value : null;

            case 'object':
                return is_object($value) ? $value : null;

            case DateTime::class:
            case DateTimeInterface::class:
                return $this->createDateTime($value);

            case DateTimeImmutable::class:
                return $this->createDateTimeImmutable($value);

            case DateInterval::class:
                return $this->createDateInterval($value);
        }

        return null;
    }

    /**
     * Creates DateTime object from the given value
     *
     * Returns null if the object cannot be created.
     *
     * @param mixed $value
     *
     * @return DateTime|null
     */
    private function createDateTime($value) : ?DateTime
    {
        if ($value instanceof DateTime) {
            return $value;
        } elseif (is_string($value)) {
            return date_create($value) ?: null;
        } elseif (is_int($value)) {
            return date_create()->setTimestamp($value);
        }

        return null;
    }

    /**
     * Creates DateTimeImmutable object from the given value
     *
     * Returns null if the object cannot be created.
     *
     * @param mixed $value
     *
     * @return DateTimeImmutable|null
     */
    private function createDateTimeImmutable($value) : ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        } elseif (is_string($value)) {
            return date_create_immutable($value) ?: null;
        } elseif (is_int($value)) {
            return date_create_immutable()->setTimestamp($value);
        }

        return null;
    }

    /**
     * Creates DateInterval object from the given value
     *
     * Returns null if the object cannot be created.
     *
     * From HTML forms it can be submitted like this:
     *
     * ```html
     * <input name="some_period[start]" value="1970-01-01">
     * <input name="some_period[end]" value="2038-01-19">
     * ```
     *
     * or like this:
     *
     * ```html
     * <input name="some_period" value="1970-01-01/2038-01-19">
     * ```
     *
     * @param mixed $value
     *
     * @return DateInterval|null
     */
    private function createDateInterval($value) : ?DateInterval
    {
        if ($value instanceof DateInterval) {
            return $value;
        }

        if (is_array($value) && isset($value['start'], $value['end'])) {
            $start = $this->createDateTime($value['start']);
            $end = $this->createDateTime($value['end']);
            if (isset($start, $end)) {
                return date_diff($start, $end) ?: null;
            }
        }

        // great, whitespaces are ignored:
        // https://github.com/php/php-src/blob/2bf451b92594a70fe745b9d27783ffe211d7940e/ext/date/lib/parse_date.c#L25124-L25131
        if (is_string($value) && false !== strpos($value, self::STRING_DATE_INTERVAL_SEPARATOR)) {
            list($start, $end) = explode(self::STRING_DATE_INTERVAL_SEPARATOR, $value, 2);
            $start = $this->createDateTime($start);
            $end = $this->createDateTime($end);
            if (isset($start, $end)) {
                return date_diff($start, $end) ?: null;
            }
        }

        return null;
    }
}
