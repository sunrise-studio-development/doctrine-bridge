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
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\String\Inflector\EnglishInflector;
use Exception;
use ReflectionClass;
use ReflectionMethod;

/**
 * Import functions
 */
use function is_array;
use function str_replace;
use function strpos;
use function ucfirst;
use function ucwords;

/**
 * Hydrator
 */
final class Hydrator
{

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * If true, then associations are filled only with reference proxies. This is faster than querying them from
     * database, but if the associated entity does not really exist, it will cause:
     * * The insert/update to fail, if there is a foreign key defined in database
     * * The record ind database also pointing to a non-existing record
     *
     * @var bool
     */
    protected $hydrateAssociationReferences = true;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $entity
     * @param array $data
     * @return mixed|object
     * @throws Exception
     */
    public function hydrate($entity, array $data)
    {
        if (is_string($entity) && class_exists($entity)) {
            $entity = new $entity;
        } elseif (!is_object($entity)) {
            throw new Exception('Entity passed to ArrayHydrator::hydrate() must be a class name or entity object');
        }

        $entity = $this->hydrateProperties($entity, $data);
        $entity = $this->hydrateAssociations($entity, $data);
        return $entity;
    }

    /**
     * @param boolean $hydrateAssociationReferences
     */
    public function setHydrateAssociationReferences($hydrateAssociationReferences)
    {
        $this->hydrateAssociationReferences = $hydrateAssociationReferences;
    }

    /**
     * @param object $entity the doctrine entity
     * @param array $data
     * @return object
     */
    protected function hydrateProperties($entity, $data)
    {
        $reflectionObject = new \ReflectionClass($entity);

        $metaData = $this->entityManager->getClassMetadata($reflectionObject->getName());
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();

        foreach ($metaData->fieldNames as $fieldName) {
            $dataKey = $fieldName;

            if (array_key_exists($dataKey, $data) && !in_array($fieldName, $metaData->identifier, true)) {
                $value = $data[$dataKey];

                if (array_key_exists('type', $metaData->fieldMappings[$fieldName])) {
                    $fieldType = $metaData->fieldMappings[$fieldName]['type'];

                    $type = Type::getType($fieldType);

                    $value = $type->convertToPHPValue($value, $platform);
                }

                $entity = $this->setProperty($entity, $fieldName, $value, $reflectionObject);
            }
        }

        return $entity;
    }

    /**
     * @param $entity
     * @param $data
     * @return mixed
     */
    protected function hydrateAssociations($entity, $data)
    {
        $metaData = $this->entityManager->getClassMetadata(get_class($entity));
        foreach ($metaData->associationMappings as $fieldName => $mapping) {
            if (!empty($data[$fieldName])) {
                if (in_array($mapping['type'], [ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE])) {
                    $entity = $this->hydrateToOneAssociation($entity, $fieldName, $mapping, $data[$fieldName]);
                }

                if (in_array($mapping['type'], [ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY])) {
                    $entity = $this->hydrateToManyAssociation($entity, $fieldName, $mapping, $data[$fieldName]);
                }
            }
        }

        return $entity;
    }

    /**
     * @param $className
     * @param $id
     * @return bool|\Doctrine\Common\Proxy\Proxy|null|object
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    protected function fetchAssociationEntity($className, $id)
    {
        if ($this->hydrateAssociationReferences) {
            return $this->entityManager->getReference($className, $id);
        }

        return $this->entityManager->find($className, $id);
    }

    /**
     * {@inheritDoc}
     */
    protected function hydrateToOneAssociation($entity, $propertyName, $mapping, $value)
    {
        $entityRef = new ReflectionClass($entity);

        $setterRef = $this->findSetterForPropertyName($propertyName, $entityRef);
        if (null === $setterRef) {
            return $entity;
        }

        if (null === $value) {
            $setterRef->invoke($entity, null);
            return $entity;
        }

        $targetEntity = is_array($value) ?
        $this->hydrate($mapping['targetEntity'], $value) :
        $this->fetchAssociationEntity($mapping['targetEntity'], $value);

        if ($targetEntity instanceof $mapping['targetEntity']) {
            $setterRef->invoke($entity, $targetEntity);
        }

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    protected function hydrateToManyAssociation($entity, $propertyName, $mapping, $value)
    {
        $entityRef = new ReflectionClass($entity);

        $adderRef = $this->findAdderForPropertyName($propertyName, $entityRef);
        if (null === $adderRef) {
            return $entity;
        }

        $values = is_array($value) ? $value : [$value];

        foreach ($values as $value) {
            $targetEntity = is_array($value) ?
            $this->hydrate($mapping['targetEntity'], $value) :
            $this->fetchAssociationEntity($mapping['targetEntity'], $value);

            if ($targetEntity instanceof $mapping['targetEntity']) {
                $adderRef->invoke($entity, $targetEntity);
            }
        }

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    protected function setProperty($entity, $propertyName, $value, $entityRef = null)
    {
        if (null === $entityRef) {
            $entityRef = new ReflectionClass($entity);
        }

        $setterRef = $this->findSetterForPropertyName($propertyName, $entityRef);
        if (null === $setterRef) {
            return $entity;
        }

        $setterRef->invoke($entity, $value);

        return $entity;
    }

    /**
     * Looks for an adder for the given property name
     *
     * @param string $propertyName
     * @param ReflectionClass $classRef
     *
     * @return null|ReflectionMethod
     */
    private function findAdderForPropertyName(string $propertyName, ReflectionClass $classRef) : ?ReflectionMethod
    {
        $propertyName = $this->camelizePropertyName($propertyName);

        // Sometimes it's not possible to determine a unique singular/plural form for the given word.
        // In those cases, the methods return an array with all the possible forms.
        $singularPropertyNames = (array) (new EnglishInflector)->singularize($propertyName);

        foreach ($singularPropertyNames as $singularPropertyName) {
            $adderName = 'add' . $singularPropertyName;
            if (!$classRef->hasMethod($adderName)) {
                continue;
            }

            $adderRef = $classRef->getMethod($adderName);
            if (!$adderRef->isPublic() || $adderRef->isStatic()) {
                break;
            }

            return $adderRef;
        }

        return null;
    }

    /**
     * Looks for a setter for the given property name
     *
     * @param string $propertyName
     * @param ReflectionClass $classRef
     *
     * @return null|ReflectionMethod
     */
    private function findSetterForPropertyName(string $propertyName, ReflectionClass $classRef) : ?ReflectionMethod
    {
        $propertyName = $this->camelizePropertyName($propertyName);

        $setterName = 'set' . $propertyName;
        if (!$classRef->hasMethod($setterName)) {
            return null;
        }

        $setterRef = $classRef->getMethod($setterName);
        if (!$setterRef->isPublic() || $setterRef->isStatic()) {
            return null;
        }

        return $setterRef;
    }

    /**
     * Converts the given property name from Snake case to Camel case
     *
     * @param string $propertyName
     *
     * @return string
     */
    private function camelizePropertyName(string $propertyName) : string
    {
        if (false === strpos($propertyName, '_')) {
            return ucfirst($propertyName);
        }

        $propertyName = str_replace('_', ' ', $propertyName);
        $propertyName = ucwords($propertyName);
        $propertyName = str_replace(' ', '', $propertyName);

        return $propertyName;
    }
}
