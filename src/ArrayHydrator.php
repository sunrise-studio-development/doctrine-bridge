<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge;

/**
 * Import classes
 */
use pmill\Doctrine\Hydrator\ArrayHydrator as BaseArrayHydrator;
use Symfony\Component\String\Inflector\EnglishInflector;
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
 * ArrayHydrator
 */
class ArrayHydrator extends BaseArrayHydrator
{

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
